function CastAVote(p){
    let o = this;
    this.actionUrl = p.actionUrl;
    this.artElemID = p.artElemID;
    this.voteElems = document.querySelectorAll(p.voteElemsSelector);
    this.voteCountSelector = p.voteCountSelector;
    this.voteDisabledClass = p.voteDisabledClass;

    this.init = function(){
        o.voteElems.forEach((voteElem)=>{

            voteElem.addEventListener('click', async (e) => {

                let voteElemParams = {
                  classes: voteElem.classList,
                  voteType: voteElem.dataset.vote,
                  artElemID: o.artElemID,
                  userVoteID: o.getCookie('USER_VOTE_ID'),
                  commentId: voteElem.dataset.commentId,
                };

                if(voteElemParams.classes.contains(o.voteDisabledClass)) return false;

                const voteCountEl = voteElem.querySelector(o.voteCountSelector);
                let voteElemCount = voteCountEl.textContent;

                voteElemParams.curCountVal = voteElemCount;

                let resObj = o.send(voteElemParams);
                resObj.then(
                    response => {
                        if(response.result === 'success'){
                            // добавлем like/dislike к комментарию
                            if(response.hasOwnProperty('res_likes')){
                               
                               voteElem.classList.add(o.voteDisabledClass, 'active');
                               voteElem.nextElementSibling.classList.add(o.voteDisabledClass);
                               let vlq = voteElem.querySelector('.j-vote-count');                               
                               vlq.textContent = response.res_likes;
                            }
                            else{
                                voteElem.classList.add(o.voteDisabledClass, 'active');
                                voteElem.previousElementSibling.classList.add(o.voteDisabledClass);
                                let vdq = voteElem.querySelector('.j-vote-count');
                                vdq.textContent = response.res_dislikes;
                            }
                        }

                    },
                    error =>{
                        console.log(error);
                    }
                );

                e.preventDefault();
            });
        });
    }


    this.send = async function(params){
        const actionUrl = o.actionUrl;
        let formData = new FormData();
        
        
        formData.append('AJAX', 'Y');
        formData.append('action', 'vote');
        formData.append('artElemID', params.artElemID);
        formData.append('voteType', params.voteType);
        formData.append('commentID', params.commentId);
        formData.append('userVoteID', params.userVoteID);        

        let response = await fetch(actionUrl, {
            method: 'POST',
            body: formData,
        });
    
        return await response.json();
    }


    // Функция для получения значения куки
    this.getCookie = function(name) {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.startsWith(name + '=')) {
                return cookie.substring(name.length + 1);
            }
        }
        return null;
    }

    // Функция для установки куки
     this.setCookie = function(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${value};expires=${date.toUTCString()}; path=/`;
    }

}
