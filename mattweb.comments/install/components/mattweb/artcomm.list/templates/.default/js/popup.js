 'use strict';
	
	 // popup
    // https://webdevtips.pro/js/pure-js-popup/
    class Popup{
        /*
        * popupSelector - popup element
        * openPopupButtonsSelector - open buttons
        * closePopupBtnSelector - close button
		* htmlVal
        * */
        constructor(opt){
           let conf = {
               commsubscrWrapSelector: '.j-commsubscr',
               popupBgSelector: '.popup__bg',
			   popupTxtSelector: '.j-result-txt',
			   eventOpenPopup: true,
           }
	
		   if(typeof opt.openPopupButtonsSelector == 'undefined') conf.eventOpenPopup = false;
		
           this.options = {
               ...conf,
               ...opt,
           }			
			
		   this.popUpBg = document.querySelector(this.options.popupBgSelector);
           this.popup = document.querySelector(this.options.popupSelector);
		   
		   if(this.options.eventOpenPopup){
			   this.openBtns = document.querySelectorAll(this.options.openPopupButtonsSelector);
		   }          
		   
		   this.closeBtn = this.popup.querySelector(this.options.closePopupBtnSelector);
		   this.popupTxt = this.popup.querySelector(this.options.popupTxtSelector);
		   
		   this.initP();
        }

        initP(){
			this.popupEvents();
			
			if(!this.options.eventOpenPopup && typeof this.options.htmlVal !== 'undefined'){				
				this.addPopupContent(this.options.htmlVal);
				this.showBg();
				this.openP();
				this.positioningCalcHeight();				
			}			
        }

        popupEvents(){
            document.addEventListener('click', (e) => {
               if(e.target === this.popUpBg){
                   this.popup.classList.remove('active');
                   this.popUpBg.classList.remove('active');
                   e.preventDefault();
               }
            });

            if(this.options.eventOpenPopup && this.openBtns.length > 0){
                
				this.openBtns.forEach((button) => {
                    button.addEventListener('click', (e) => {
                        
                        const replyIDSelector = this.options.replyIDSelector;                        
                        const commsubscrWrap = document.querySelector(this.options.commsubscrWrapSelector);

                        this.popup.querySelector(replyIDSelector).value = '';
                        
                        // Set the value of the hidden field if reply button clicked. 
                        // The value is replied comment ID
                        if(e.target.classList.contains('j-reply')){
                            let parentCommId = e.target.dataset.commentId;
                            this.popup.querySelector(replyIDSelector).value = parentCommId;
                            
                            if(commsubscrWrap.classList.contains('dn')){
                                commsubscrWrap.classList.remove('dn');  
                            }
                        }                        

						this.showBg();
                        this.openP();
						this.positioningCalcHeight();
                        
						e.preventDefault();
                    });

                });
            }

            this.closeBtn.addEventListener('click', (e) => {
                const commsubscrWrap = document.querySelector(this.options.commsubscrWrapSelector);
                if(!commsubscrWrap.classList.contains('dn')){
                    commsubscrWrap.classList.add('dn');
                }              
                
                this.hideBg();
				this.closeP();
                e.preventDefault();
            });

        }

        positioningCalcHeight(){
			let clentHeight = document.documentElement.clientHeight; // высота видимой области            
			let heightOffSetTop = document.documentElement.scrollTop; // отступ сверху
            let popupHeight = this.popup.clientHeight; // высота popup

            
			//let heightPos = (heightOffSetTop > clentHeight) ? (heightOffSetTop + clentHeight / 2 - popupHeight / 2) : (clentHeight / 2 - popupHeight / 2);
			let heightPos = heightOffSetTop + clentHeight / 2 - popupHeight / 2;

			if(heightPos > 0){
				this.popup.style.top = heightPos + 'px';
			}           
        }

        openP(){            
            this.popup.classList.add('active');

        }

        closeP(){            
            this.popup.classList.remove('active');
        }
		
		showBg(){
			this.popUpBg.classList.add('active');
		}
		
		hideBg(){
			this.popUpBg.classList.remove('active');
		}
		
		
		addPopupContent(html){
			this.popupTxt.innerHTML = html;
		}

    }