    // form validation
    // https://javascript.plainenglish.io/proper-form-validation-with-code-ee7777bd2141
    // https://www.internet-technologies.ru/articles/validaciya-formy-registracii-s-pomoschyu-javascript.html
    // https://www.youtube.com/watch?v=UKK7ID43DuI

    function formSubmit(options){
        
        
        let _this = this;
        this.options = options;

        if(typeof options.validate === 'undefined'){
            this.options.validate = true;
        }

         console.dir('options');
         console.dir(options);
                
        const forms = document.forms;

        if(forms.length > 0){
            for (let form of forms){
                form.addEventListener('submit', (function(e){
					const form = e.target;
					formSubmitAction(form, e);
                }));				
				
                form.addEventListener('reset', (function (e){
                    const form = e.target;
                    formValidate.formClean(form);
                }));
            }
        }

        /*Функция-обработчик отправки форм*/
        async function formSubmitAction(form, e){
            
            // console.log('form submit action');
            // console.dir(_this.options);


			const error = !form.hasAttribute('data-no-validate') ? formValidate.getErrors(form) : 0;
            //console.log(error);
			if(0 === error){
				const ajax = form.hasAttribute('data-ajax');
				if(ajax){
					e.preventDefault();
		
					const formAction = form.getAttribute('action') ? form.getAttribute('action') : '#';
					const formMethod = form.getAttribute('method') ? form.getAttribute('method') : 'GET';

                    const curLeaveComment = form.parentElement.parentElement;

					const formData = new FormData(form);
                    
                    if(_this.options.useRecaptcha === 'Y' && _this.options.recaptchaSCK !== ''){
                        formData.append('recaptchaSCK', _this.options.recaptchaSCK);
                    }                   

					form.classList.add('_sending');
                    curLeaveComment.classList.remove('active');
					const response = await fetch(formAction, {
						method: formMethod,
						body: formData
					});

                    if(response.ok){
                        let responseResult = await response.json();
                        let fullResMessage = null;
                        if(responseResult.result === 'success'){
                            fullResMessage = responseResult.res_message;

                        }

                        if(responseResult.result === 'error'){
                            let errMessages = '';
                            for(let em of responseResult.res_message){
                                errMessages += '<li>' + em + '</li>';
                            }
                            fullResMessage = `<ul class="list-errors">${errMessages}</ul>`;
                        }

                        formResult(form, responseResult.result, fullResMessage);
                    }else{
                        // Обработка ошибок при отправке через AJAX
                        console.log('HTTP error: ' + response.status + ' ' + response.statusText);
                        form.classList.remove('_sending');
                    }

				}else if (form.hasAttribute("data-dev")) {
                    // заглушка для провеки формы без отправки
                    e.preventDefault();
                    formResult(form);
                }
			} else {
                /*Если есть ошибки при валидации - форму не отправляем*/
                e.preventDefault();
            }

        }

        /*
        Функция запускается после отправки формы
        Обработчика этого событяи нет. При необходимости его можно написать
        */
        function formResult(form, responseResult, responseResultHtml = ``){
			/*Запуск пользовательского события formResult*/
			document.dispatchEvent(new CustomEvent('formResult', {
				detail: {
					form,
                    responseResultHtml
				}
			}));

			if(responseResult === 'success'){
                formValidate.formClean(form);
            }
        }
    }

    document.addEventListener('formResult', (e)=>{
        const popupResultSelector = '.j-form-res-block';
        const popupCloseSelector = '.j-close-popup';

        let resPopup = new Popup({
            popupSelector: popupResultSelector,
            closePopupBtnSelector: popupCloseSelector,
            htmlVal: e.detail.responseResultHtml,
        });
    });
