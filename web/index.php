<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

$dbopts = parse_url(getenv('DATABASE_URL'));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_pgsql',
        'user' => $dbopts["user"],
        'password' => $dbopts["pass"],
        'host' => $dbopts["host"],
        'port' => $dbopts["port"],
        'dbname' => ltrim($dbopts["path"], '/')
       )
));

// $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
//     'db.options' => array(
//        'driver'   => 'pdo_pgsql',
//        'user' => 'postgres',
//        'password' => 'toor',
//        'host' => 'localhost',
//        'port' => 5432,
//        'dbname' => 'staff_payroll'
//        )
// ));

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'login_path' => array(
            'pattern' => '^/login$',
            'anonymous' => true
        ),
        'default' => array(
            'pattern' => '^/.*$',
            'anonymous' => true,
            'form' => array(
                'login_path' => '/login',
                'check_path' => 'login_check'
            ),
            'logout' => array(
                'logout_path' => '/logout',
                'invalidate_session' => false
            ),
            'users' => function () use ($app) {
                return new App\User\UserProvider($app['db']);
            },
        )
    ),
    'security.access_rules' => array(
        array('^/login$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/.+$', 'IS_AUTHENTICATED_FULLY'),
    )
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// function

$app->get('/', function (Silex\Application $app) {
    return $app['twig']->render(
        'index.html.twig',
        array(
            'title' => 'Home'
        )
    );
})->bind('index');

$app->get('/info', function (Silex\Application $app) {
    return phpinfo();
});

$app->get('/login', function (Silex\Application $app, Request $request) {
    return $app['twig']->render(
        'login.html.twig',
        array(
            'title' => 'Login',
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        )
    );
})->bind('login');

$app->get('/stafflist', function () use ($app) {
    $stafflists = $app['db']->fetchAll('SELECT * FROM staff');
    return $app['twig']->render('stafflist.html.twig', array(
        'title' => 'Staff List',
        'stafflists' => $stafflists
    ));
})->bind('stafflist');

$app->get('/staff/{id}', function (Silex\Application $app, $id) {
    $profile = $app['db']->fetchAssoc("SELECT * FROM staff, account, department where staff.staff_id = '$id' AND staff.staff_id = account.staff_id AND department.depart_id = staff.depart_id");
    if (!$profile) {
        $app->abort(404, "Staff $id could not be found");
    }
    return $app['twig']->render('staff.html.twig', array(
        'title' => "Staff $id",
        'profile' => $profile
    ));
})
    ->assert('id', '\d+')
    ->bind('staff');

$app->get('/staffpayroll/{id}', function (Silex\Application $app, $id) {
    $payrolls = $app['db']->fetchAll("SELECT * FROM staff, account, payroll, department where staff.staff_id = '$id' AND staff.staff_id = account.staff_id AND staff.staff_id = payroll.staff_id AND department.depart_id = staff.depart_id");
    if (!$payrolls) {
        $app->abort(404, "Staff $id could not be found");
    }
    return $app['twig']->render('staffpayroll.html.twig', array(
            'title' => "Staff $id",
            'payrolls' => $payrolls
        ));
})
        ->assert('id', '\d+')
        ->bind('staffpayroll');

$app->get('/profile', function () use ($app) {
    $usr = $app['security.token_storage']->getToken()->getUser();
    $id = $usr->getUsername();
    $profile = $app['db']->fetchAssoc("SELECT * FROM staff, account, department where username = '$id' AND staff.staff_id = account.staff_id AND department.depart_id = staff.depart_id");
    return $app['twig']->render('profile.html.twig', array(
        'title' => 'Profile',
        'profile' => $profile
    ));
})->bind('profile');

$app->post('/profile/edit', function (Silex\Application $app, Request $request) {

    $staff_id = $app->escape($request->get('staff_id'));
    $first_name = $app->escape($request->get('first_name'));
    $last_name = $app->escape($request->get('last_name'));
    $dob = $app->escape($request->get('dob'));
    $gender = $app->escape($request->get('gender'));
    $email = $app->escape($request->get('email'));
    $is_active = $app->escape($request->get('is_active'));
    $addr = $app->escape($request->get('addr'));
    $addr2 = $app->escape($request->get('addr2'));
    $state = $app->escape($request->get('state'));
    $city = $app->escape($request->get('city'));
    $postal_code = $app->escape($request->get('postal_code'));
    $phone_home = $app->escape($request->get('phone_home'));
    $phone_personal = $app->escape($request->get('phone_personal'));

    $count = $app['db']->executeUpdate("UPDATE staff SET first_name = '$first_name', last_name = '$last_name', dob = '$dob', gender = '$gender', email = '$email', is_active = '$is_active', addr = '$addr', addr2 = '$addr2', state = '$state', city = '$city', postal_code = '$postal_code', phone_home = '$phone_personal' WHERE staff_id = '$staff_id'");
    return new Response("$count column updated. <script>setTimeout(function(){window.location.replace('/');}, 3000);</script>", 200);
})->bind('profileEdit');

$app->get('/payroll', function () use ($app) {
    $usr = $app['security.token_storage']->getToken()->getUser();
    $id = $usr->getUsername();
    $payrolls = $app['db']->fetchAll("SELECT * FROM staff, account, payroll, department where username = '$id' AND staff.staff_id = account.staff_id AND payroll.staff_id = staff.staff_id AND department.depart_id = staff.depart_id");
    return $app['twig']->render('payroll.html.twig', array(
        'title' => 'Payroll',
        'payrolls' => $payrolls
    ));
})->bind('payroll');


$app->run();
