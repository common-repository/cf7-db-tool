
var MailgunInput = document.querySelectorAll(".mailgun-input");
var SendGridInput = document.querySelectorAll(".sendgrid-input");
var chooseMailer = document.querySelector("#choose-mailer");

(function ($) {

	$(document).ready(function(){

		$('.active_mail_provider').on('change', function(){
			if(this.checked){
				chooseMailer.setAttribute('required','true');
			}
			else{
				chooseMailer.removeAttribute('required');
				SendGridInput.forEach(function(sendGridFiled){
					sendGridFiled.removeAttribute('required');
				});
				MailgunInput.forEach(function(mailgunFiled){
					mailgunFiled.removeAttribute('required');
				});

			}
		});

		$("#choose-mailer").on('change', function() {

			var SelectedProvider = $(this).find(":selected").val().trim();
			if(SelectedProvider == 'Mailgun'){
				MailgunInput.forEach(function(mailgunFiled){
					mailgunFiled.setAttribute('required','true');
				});
				SendGridInput.forEach(function(sendGridFiled){
					sendGridFiled.removeAttribute('required');
				});
			}
			if(SelectedProvider == 'SendGrid'){
				SendGridInput.forEach(function(sendGridFiled){
					sendGridFiled.setAttribute('required','true');
				});
				MailgunInput.forEach(function(mailgunFiled){
					mailgunFiled.removeAttribute('required');
				});

			}
		});
	});
})(jQuery);
