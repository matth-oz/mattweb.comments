    let formValidate = {
		/*валидация всех полей формы*/
        getErrors(form){
			let error = 0;			
			let formRequiredItems = form.querySelectorAll('*[data-required]');
			if(formRequiredItems.length > 0){
				formRequiredItems.forEach((formRequiredItem => {
					if((null !== formRequiredItem.offsetParent || "SELECT" === formRequiredItem.tagName) && !formRequiredItem.disabled) error += this.validateInput(formRequiredItem);					
				}));
			}
					
			return error;
        },
		/*валидация отдельного поля формы*/
        validateInput(formRequiredItem){
			let error = 0;
			
			if('email' === formRequiredItem.dataset.required){
				formRequiredItem.value = formRequiredItem.value.replace(' ', '');
				if(this.emailTest(formRequiredItem)){
					this.addError(formRequiredItem);
					error++;
				}else{
					this.removeError(formRequiredItem);
				}
			}else if('name' === formRequiredItem.dataset.required){
				formRequiredItem.value = formRequiredItem.value.replace(' ', '');
				if(formRequiredItem.value.length == 0){
					this.addError(formRequiredItem);
					error++;
				}else{
					this.removeError(formRequiredItem);
				}				
			}else if('message' === formRequiredItem.dataset.required){
				formRequiredItem.value = formRequiredItem.value.replace(' ', '');
				if(formRequiredItem.value.length == 0){
					this.addError(formRequiredItem);
					error++;
				}else{
					this.removeError(formRequiredItem);
				}
			}
            return error;
        },
		/*добавляет и отображает ошибку у формы*/
        addError(formRequiredItem){
			formRequiredItem.classList.add('_form-error');
			//formRequiredItem.parentElement.classList.add('_form-error');
			let inputError = formRequiredItem.parentElement.querySelector('.form__error');
			if(inputError) formRequiredItem.parentElement.removeChild(inputError);
			if(formRequiredItem.dataset.error) formRequiredItem.parentElement.insertAdjacentHTML('beforeend', `<div class="form__error">${formRequiredItem.dataset.error}</div>`);
        },
		/*убирает ошибку у формы*/
        removeError(formRequiredItem){
			formRequiredItem.classList.remove('_form-error');
			formRequiredItem.parentElement.classList.remove('_form-error');
			if(formRequiredItem.parentElement.querySelector('.form__error')){
				formRequiredItem.parentElement.removeChild(formRequiredItem.parentElement.querySelector('.form__error'));
			}
        },
		/*очищает форму после отправки*/
        formClean(form){
            form.reset();
            setTimeout((() => {
                let inputs = form.querySelectorAll('input, textarea');
                for(let index = 0; index < inputs.length; index++){
                    const el = inputs[index];
                    el.parentElement.classList.remove('_form-focus');
					formValidate.removeError(el);
                }
				
				let checkboxes = form.querySelectorAll('.checkbox__input');
				if(checkboxes.length > 0){
					for(let index = 0; index < checkboxes.length; index++){
						const checkbox = checkboxes[index];
						checkbox.checked = false;
					}
				}

            }), 0);
        },
		/*проверка email по регулярному выражению*/
        emailTest(formRequiredItem){
            return !/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,8})+$/.test(formRequiredItem.value);
        },
		/*проверка возраст по регулярному выражению*/
        ageTest(formRequiredItem){
            return !/^[0-9]{2}$/.test(formRequiredItem.value);
        }
    }