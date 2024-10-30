(function ($) {
	"use strict";
	var cf7DbtJs = {
		showReplyForm: function () {
			$('.cf7-dbt-show-reply-form').on('click',function (e) {
				e.preventDefault();
				$('.cf7-dbt-reply-form-container').slideDown(300);
				$(this).hide();

			})
		},
		handleSubmit: function(){
			$('#cf7-dbt-reply-form').on('submit',function (e) {
				e.preventDefault();
				var formData = $(this).serializeArray();
				if($('.cf7-dbt-form-field').hasClass('has-error')){
					$('.cf7-dbt-form-field').removeClass('has-error').children('.cf7-dbt-form-error').html('');
				}
				$.ajax({
					method: 'POST',
					url: cf7DbtObj.ajaxUrl,
					beforeSend: function (xhr) {
						$('.cf7-dbt-loader').fadeIn(200);
					},
					data: {
						action : 'cf7_dbt_reply',
						security: cf7DbtObj.nonce,
						formData : formData
					},
					success: function (r) {
						console.log(r);
						r = $.parseJSON(r);
						if(r.hasOwnProperty('hasError')){
							if(r.errorType === 'emptyMsg'){
								$('.cf7-dbt-msg-field').addClass('has-error').children('.cf7-dbt-form-error').html(r.message);

							}
							if(r.mailSent === false){
								$('.cf7-dbt-reply-status').fadeIn(300).addClass('failed');
							}
						}
						if(r.mailSent === true){
							$('.cf7-dbt-reply-status').fadeIn(300).addClass('success');
						}

					},
					error: function (r) {
						console.log(r);
					},
					complete: function () {
						$('.cf7-dbt-loader').fadeOut(200);
					}
				});
			});
		},
		cancelReplyForm: function(){
			$('.cf7-dbt-cancel-reply').on('click',function (e) {
				e.preventDefault();
				$('.cf7-dbt-reply-form-container').slideUp(300);
				$('.cf7-dbt-show-reply-form').show(350);
			})
		},
		init: function () {
			this.showReplyForm();
			this.handleSubmit();
			this.cancelReplyForm();
		}
	};
	cf7DbtJs.init();

})(jQuery);