<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../vendor/autoload.php'; // Add the autoloading mechanism of Composer

$app = new Silex\Application(); // Create the Silex application, in which all configuration is going to go



// Section A
// We will later add the configuration, etc. here

$app['debug'] = true;
$app['articles'] = array(
    array(
        'title'    => 'Lorem ipsum dolor sit amet',
        'contents' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean mollis vestibulum ultricies. Sed sit amet sagittis nisl. Nulla leo metus, efficitur non risus ut, tempus convallis sem. Mauris pharetra sagittis ligula pharetra accumsan. Cras auctor porta enim, a eleifend enim volutpat vel. Nam volutpat maximus luctus. Phasellus interdum elementum nulla, nec mollis justo imperdiet ac. Duis arcu dolor, ultrices eu libero a, luctus sollicitudin diam. Phasellus finibus dictum turpis, nec tincidunt lacus ullamcorper et. Praesent laoreet odio lacus, nec lobortis est ultrices in. Etiam facilisis elementum lorem ut blandit. Nunc faucibus rutrum nulla quis convallis. Fusce molestie odio eu mauris molestie, a tempus lorem volutpat. Sed eu lacus eu velit tincidunt sodales nec et felis. Nullam velit ex, pharetra non lorem in, fringilla tristique dolor. Mauris vel erat nibh.',
        'author'   => 'Sammy',
        'date'     => '2014-12-18',
    ),
    array(
        'title'    => 'Duis ornare',
        'contents' => 'Duis ornare, odio sit amet euismod vulputate, purus dui fringilla neque, quis eleifend purus felis ut odio. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Pellentesque bibendum pretium ante, eu aliquet dolor feugiat et. Pellentesque laoreet est lectus, vitae vulputate libero sollicitudin consequat. Vivamus finibus interdum egestas. Nam sagittis vulputate lacus, non condimentum sapien lobortis a. Sed ligula ante, ultrices ut ullamcorper nec, facilisis ac mi. Nam in vehicula justo. In hac habitasse platea dictumst. Duis accumsan pellentesque turpis, nec eleifend ex suscipit commodo.',
        'author'   => 'Sammy',
        'date'     => '2014-11-08',
    ),
);

$dbopts = parse_url(getenv('DATABASE_URL'));
$app->register(new Csanquer\Silex\PdoServiceProvider\Provider\PDOServiceProvider('pdo'),
    array(
     'pdo.server' => array(
        'driver'   => 'pgsql',
        'user' => $dbopts["user"],
        'password' => $dbopts["pass"],
        'host' => $dbopts["host"],
        'port' => $dbopts["port"],
        'dbname' => ltrim($dbopts["path"],'/')
        )
    )
);

// $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
//     'db.options' => array(
//        'driver'   => 'pgsql',
//        'user' => $dbopts["user"],
//        'password' => $dbopts["pass"],
//        'host' => $dbopts["host"],
//        'port' => $dbopts["port"],
//        'dbname' => ltrim($dbopts["path"],'/')
//        )
// ));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views', // The path to the templates, which is in our case points to /var/www/templates
));





$app->get('/', function (Silex\Application $app)  { // Match the root route (/) and supply the application as argument
    return $app['twig']->render( // Render the page index.html.twig
        'index.html.twig',
        array(
            'articles' => $app['articles'], // Supply arguments to be used in the template
        )
    );
})->bind('index');

$app->get('/info', function (Silex\Application $app)  { // Match the root route (/) and supply the application as argument
    return phpinfo();
});

$app->get('/login', function (Silex\Application $app)  { // Match the root route (/) and supply the application as argument
    return $app['twig']->render( // Render the page index.html.twig
        'login.html.twig'
    );
})->bind('login');

$app->post('/actionlogin', function (Request $request) {
    $username = $request->get('username');
    if ($username == null) {
        $app->abort(400, 'Username is empty.');
    }
    $password = $request->get('password');
    if ($password == null) {
        $app->abort(400, 'Password is empty.');
    }
    $type = $request->get('type');
    if ($type == null) {
        $app->abort(400, 'Type is missing.');
    }
    return new Response("$username $password $type", 200);
})->bind('actionlogin');

$app->get('/stafflist', function () use($app) {
    $st = $app['pdo']->prepare('SELECT * FROM staff');
    $st->execute();

    $stafflists = array();
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $stafflists[] = $row;
    }

    return $app['twig']->render('database.twig', array(
        'stafflists' => $stafflists
    ));

    // $stafflists = $app['db']->fetchAll('SELECT * FROM table');
    // return $app['twig']->render('database.twig', array(
    //     'stafflists' => $stafflists
    // ));

    // $dbconn3 = pg_connect("host=localhost port=5432 dbname=staff_payroll user=postgres password=toor");

    // return "test";
})->bind('stafflist');

$app->get('/{id}', function (Silex\Application $app, $id)  { // Add a parameter for an ID in the route, and it will be supplied as argument in the function
    if (!array_key_exists($id, $app['articles'])) {
        $app->abort(404, 'The article could not be found');
    }
    $article = $app['articles'][$id];
    return $app['twig']->render(
        'single.html.twig',
        array(
            'article' => $article,
        )
    );
})
    ->assert('id', '\d+') // specify that the ID should be an integer
    ->bind('single'); // name the route so it can be referred to later in the section 'Generating routes'




// This should be the last line
$app->run(); // Start the application, i.e. handle the request
?>
