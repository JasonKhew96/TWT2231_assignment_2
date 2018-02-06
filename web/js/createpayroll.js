window.onload = function() {
	$('#att_hrs').change(function() {
		calc();
	});
	$('#ot_hrs').change(function() {
		calc();
	});
};

function calc(){
	var salary_rate = $('#salary_rate').val();
	var att_hrs = $('#att_hrs').val();
	var ot_rate = $('#ot_rate').val();
	var ot_hrs = $('#ot_hrs').val();
	var total = salary_rate*att_hrs + ot_rate*ot_hrs;
	$('#total').val(total);
}
