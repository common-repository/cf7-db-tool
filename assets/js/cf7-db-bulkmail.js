(function ($) {
	'use-strict';

	// Mail list will store in here from CSV
	var mailFromCsv =[];

	$(document).ready(function(){

		$('#uploadCSV').change(function() {

/*
* Email validation function
* */
			function checkMail(email){

				var email = email.replace(/\s/g, '');
				var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
				if (!filter.test(email)) {
					return false
				}
				return  true

			}

			var fileUpload = document.getElementById("uploadCSV");

			var regex = /^([a-zA-Z0-9\s_\\.\-:])+(.csv|.txt)$/;
			if (regex.test(fileUpload.value.toLowerCase())) {
				if (typeof (FileReader) != "undefined") {
					var reader = new FileReader();
					reader.onload = function (e) {
						var table = document.createElement("table");
						var rows = e.target.result.split("\n");
						for (var i = 0; i < rows.length; i++) {
							var cells = rows[i].split(",");
							if (cells.length > 1) {
								for (var j = 0; j < cells.length; j++) {
									if(cells[j].includes(".com")){
										if(checkMail(cells[j])){
											mailFromCsv.push((cells[j]));
										}
									}
								}
							}
						}
						loadMialListFromCsv(mailFromCsv)
						//console.log(mailFromCsv)
					}
					reader.readAsText(fileUpload.files[0]);
				} else {
					alert("This browser does not support HTML5.");
				}
			} else {
				alert("Please upload a valid CSV file.");
			}

		});

/*
* Ajax for load form mail
* */
		var OBJ = {
			cf7form : 0,
		}
		$('#select-session_list').on('change', function() {
			OBJ.cf7form = $(this).find(":selected").val() ;

			if($(this).find(":selected").val() == 'csv'){
				csvUpload.style.cssText ="display:block";
			}else{
				callFromDB();
			}
		});
		function callFromDB() {
			if(OBJ.cf7form != 0 ){

				$.post(ajax_object.ajax_url,{
					'action':'bulkMailAjaxDataAction',
					'cf7form': OBJ.cf7form,
				},function(data){
					if(data == 0){
						alert('No Data Found');
					}else{
						$('#dbtool-user-list').html(data);
					}
				})

			}
		}
	});
})(jQuery);
// arguments: reference to select list, callback function (optional)

var Totalusers = document.getElementById("dbtool-user-list");
var resetButton = document.getElementById('ResetData');
var TargetToInputForm = document.getElementById('dbtool-recipient-emails');
var csvUpload = document.querySelector('.uploadByCsv');
var csvFromForms = document.getElementById('select-session_list');




/*
* Show mail in dashboard from csv
* */
function loadMialListFromCsv(data){
	Totalusers.innerHTML = '';
	for(index in data) {
		Totalusers.options[Totalusers.options.length] = new Option(data[index], data[index]);
	}
	console.log("Upload Successfully");
}

function getSelectedOptions(sel, fn) {
	var opts = [], opt;
	// loop through options in select list
	for (var i=0, len=sel.options.length; i<len; i++) {
		opt = sel.options[i];
		// check if selected
		if ( opt.selected && !TargetToInputForm.value.includes(opt.value) ) {
			// add to array of option elements to return from this function
			opts.push(opt);
			// invoke optional callback function if provided
			if (fn) {
				fn(opt);
			}
		}
	}

	// return array containing references to selected option elements
	return opts;
}
// example callback function (selected options passed one by one)
function callback(opt) {
	// display in textarea for this example
	opt.setAttribute("disabled",'true')
	if(TargetToInputForm.value == ''){
		TargetToInputForm.value += opt.value;
	}else{
		TargetToInputForm.value += ',' + opt.value;
	}
}
// anonymous function onchange for select list with id demoSel
document.getElementById('dbtool-user-list').onchange = function(e) {
	// get reference to display textarea

	// callback fn handles selected options
	getSelectedOptions(this, callback);
	if(resetButton.computedStyleMap().get('cursor').value=='not-allowed') {
		resetButton.style.cssText = "opacity: 1;cursor: pointer;"
	}
};

// Funtion for select all email to receiver input
document.getElementById('select_all').onclick = function(e) {
	TargetToInputForm.value = '';
	var emailList = "";

	for (var i = 0; i < Totalusers.length; i++) {
		emailList = emailList + "\n" + Totalusers.options[i].value + ',';
		Totalusers.options[i].setAttribute("disabled",'true');
	}
	emailList = emailList.slice(0, emailList.length-1);
	TargetToInputForm.value = emailList;
	resetButton.style.cssText  = "opacity: 1;cursor: pointer;"
	return false; // don't return online form
};

// Reset function for disable remove from mail list
resetButton.onclick = function(e) {
	for (var i = 0; i < Totalusers.length; i++) {
		Totalusers.options[i].removeAttribute("disabled");
	}
	this.style.cssText  = "opacity: 0.65;cursor: not-allowed;"
}