/*! For license information please see authorize-authorize-card.js.LICENSE.txt */
(()=>{function e(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}var t=function(){function t(e,a){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,t),this.publicKey=e,this.loginId=a,this.cardHolderName=document.getElementById("cardholder_name"),this.cardButton=document.getElementById("card_button")}var a,n,r;return a=t,(n=[{key:"handleAuthorization",value:function(){var e=$("#my-card"),t={};t.clientKey=this.publicKey,t.apiLoginID=this.loginId;var a={};a.cardNumber=e.CardJs("cardNumber").replace(/[^\d]/g,""),a.month=e.CardJs("expiryMonth").replace(/[^\d]/g,""),a.year=e.CardJs("expiryYear").replace(/[^\d]/g,""),a.cardCode=document.getElementById("cvv").value.replace(/[^\d]/g,"");var n={};return n.authData=t,n.cardData=a,document.getElementById("card_button").disabled=!0,document.querySelector("#card_button > svg").classList.remove("hidden"),document.querySelector("#card_button > span").classList.add("hidden"),Accept.dispatchData(n,this.responseHandler),!1}},{key:"responseHandler",value:function(e){return"Error"===e.messages.resultCode?($("#errors").show().html("<p>"+e.messages.message[0].code+": "+e.messages.message[0].text+"</p>"),document.getElementById("card_button").disabled=!1,document.querySelector("#card_button > svg").classList.add("hidden"),document.querySelector("#card_button > span").classList.remove("hidden")):"Ok"===e.messages.resultCode&&(document.getElementById("dataDescriptor").value=e.opaqueData.dataDescriptor,document.getElementById("dataValue").value=e.opaqueData.dataValue,document.getElementById("server_response").submit()),!1}},{key:"handle",value:function(){var e=this;return this.cardButton.addEventListener("click",(function(){e.cardButton.disabled=!e.cardButton.disabled,e.handleAuthorization()})),this}}])&&e(a.prototype,n),r&&e(a,r),t}();new t(document.querySelector('meta[name="authorize-public-key"]').content,document.querySelector('meta[name="authorize-login-id"]').content).handle()})();