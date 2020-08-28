/**
 * welight Transparente para Magento
 * @author Ricardo Martins <ricardo@Gateway.net.br>
 * @link https://github.com/r-martins/welight-Magento-Transparente
 * @version 3.8.2
 */

RMwelight = Class.create({
    initialize: function (config) {
        this.config = config;

        if (!config.welightSessionId) {
            console.error('Falha ao obter sessão junto ao welight. Verifique suas credenciais, configurações e logs de erro.')
        }
        welightDirectPayment.setSessionId(config.welightSessionId);

        // this.updateSenderHash();
        welightDirectPayment.onSenderHashReady(this.updateSenderHash);

        if (typeof config.checkoutFormElm == "undefined") {
            var methods= $$('#p_method_rm_welight_cc', '#p_method_welightpro_boleto', '#p_method_welightpro_tef');
            if(!methods.length){
                console.log('welight: Não há métodos de pagamento habilitados em exibição. Execução abortada.');
                return;
            }else{
                var form = methods.first().closest('form');
                form.observe('submit', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    RMwelightObj.formElementAndSubmit = e.element();
                    RMwelightObj.updateCreditCardToken();
                });
            }
        }

        if(config.welightSessionId == false){
            console.error('Não foi possível obter o SessionId do welight. Verifique seu token, chave e configurações.');
        }
        console.log('RMwelight prototype class has been initialized.');

        this.maxSenderHashAttempts = 30;

        //internal control to avoid duplicated calls to updateCreditCardToken
        this.updatingCreditCardToken = false;
        this.formElementAndSubmit = false;


        Validation.add('validate-welight', 'Falha ao atualizar dados do pagaento. Entre novamente com seus dados.',
            function(v, el){
                RMwelightObj.updatePaymentHashes();
                return true;
        });
    },
    updateSenderHash: function(response) {
        if(typeof response === 'undefined'){
            welightDirectPayment.onSenderHashReady(RMwelightObj.updateSenderHash);
            return false;
        }
        if(response.status == 'error'){
            console.log('welight: Falha ao obter o senderHash. ' + response.message);
            return false;
        }
        RMwelightObj.senderHash = response.senderHash;
        RMwelightObj.updatePaymentHashes();

        return true;
    },

    getInstallments: function(grandTotal, selectedInstallment){
        var brandName = "";
        if(typeof RMwelightObj.brand == "undefined"){
            return;
        }
        if(!grandTotal){
            grandTotal = this.getGrandTotal();
            return;
        }
        this.grandTotal = grandTotal;
        brandName = RMwelightObj.brand.name;

        var parcelsDrop = $('rm_welight_cc_cc_installments');
        if(!selectedInstallment && parcelsDrop.value != ""){
            selectedInstallment = parcelsDrop.value.split('|').first();
        }
        welightDirectPayment.getInstallments({
            amount: grandTotal,
            brand: brandName,
            success: function(response) {
                for(installment in response.installments) break;
//                       console.log(response.installments);
//                 var responseBrand = Object.keys(response.installments)[0];
//                 var b = response.installments[responseBrand];
                var b = Object.values(response.installments)[0];
                parcelsDrop.length = 0;

                if(RMwelightObj.config.force_installments_selection){
                    var option = document.createElement('option');
                    option.text = "Selecione a quantidade de parcelas";
                    option.value = "";
                    parcelsDrop.add(option);
                }

                var installment_limit = RMwelightObj.config.installment_limit;
                for(var x=0; x < b.length; x++){
                    var option = document.createElement('option');
                    option.text = b[x].quantity + "x de R$" + b[x].installmentAmount.toFixed(2).toString().replace('.',',');
                    option.text += (b[x].interestFree)?" sem juros":" com juros";
                    if(RMwelightObj.config.show_total){
                        option.text += " (total R$" + (b[x].installmentAmount*b[x].quantity).toFixed(2).toString().replace('.', ',') + ")";
                    }
                    option.selected = (b[x].quantity == selectedInstallment);
                    option.value = b[x].quantity + "|" + b[x].installmentAmount;
                    if (installment_limit != 0 && installment_limit <= x) {
                        break;
                    }
                    parcelsDrop.add(option);
                }
//                       console.log(b[0].quantity);
//                       console.log(b[0].installmentAmount);

            },
            error: function(response) {
                parcelsDrop.length = 0;

                var option = document.createElement('option');
                option.text = "1x de R$" + RMwelightObj.grandTotal.toFixed(2).toString().replace('.',',') + " sem juros";
                option.selected = true;
                option.value = "1|" + RMwelightObj.grandTotal.toFixed(2);
                parcelsDrop.add(option);

                var option = document.createElement('option');
                option.text = "Falha ao obter demais parcelas junto ao welight";
                option.value = "";
                parcelsDrop.add(option);

                console.error('Somente uma parcela será exibida. Erro ao obter parcelas junto ao welight:');
                console.error(response);
            },
            complete: function(response) {
//                       console.log(response);
//                 RMwelight.reCheckSenderHash();
            }
        });
    },

    addCardFieldsObserver: function(obj){
        try {
            var ccNumElm = $$('input[name="payment[ps_cc_number]"]').first();
            var ccExpMoElm = $$('select[name="payment[ps_cc_exp_month]"]').first();
            var ccExpYrElm = $$('select[name="payment[ps_cc_exp_year]"]').first();
            var ccCvvElm = $$('input[name="payment[ps_cc_cid]"]').first();

            Element.observe(ccNumElm,'change',function(e){obj.updateCreditCardToken();});
            Element.observe(ccExpMoElm,'change',function(e){obj.updateCreditCardToken();});
            Element.observe(ccExpYrElm,'change',function(e){obj.updateCreditCardToken();});
            Element.observe(ccCvvElm,'change',function(e){obj.updateCreditCardToken();});
        }catch(e){
            console.error('Não foi possível adicionar observevação aos cartões. ' + e.message);
        }

    },
    updateCreditCardToken: function(){
        var ccNum = $$('input[name="payment[ps_cc_number]"]').first().value.replace(/^\s+|\s+$/g,'');
        // var ccNumElm = $$('input[name="payment[ps_cc_number]"]').first();
        var ccExpMo = $$('select[name="payment[ps_cc_exp_month]"]').first().value.replace(/^\s+|\s+$/g,'');
        var ccExpYr = $$('select[name="payment[ps_cc_exp_year]"]').first().value.replace(/^\s+|\s+$/g,'');
        var ccCvv = $$('input[name="payment[ps_cc_cid]"]').first().value.replace(/^\s+|\s+$/g,'');

        var brandName = '';
        if(typeof RMwelightObj.lastCcNum != "undefined" || ccNum != RMwelightObj.lastCcNum){
            this.updateBrand();
            if(typeof RMwelightObj.brand != "undefined"){
                brandName = RMwelightObj.brand.name;
            }
        }

        if(ccNum.length > 6 && ccExpMo != "" && ccExpYr != "" && ccCvv.length >= 3)
        {
            if(this.updatingCreditCardToken){
                return;
            }
            this.updatingCreditCardToken = true;

            RMwelightObj.disablePlaceOrderButton();
            welightDirectPayment.createCardToken({
                cardNumber: ccNum,
                brand: brandName,
                cvv: ccCvv,
                expirationMonth: ccExpMo,
                expirationYear: ccExpYr,
                success: function(psresponse){
                    RMwelightObj.creditCardToken = psresponse.card.token;
                    var formElementAndSubmit = RMwelightObj.formElementAndSubmit;
                    RMwelightObj.formElementAndSubmit = false;
                    RMwelightObj.updatePaymentHashes(formElementAndSubmit);
                    $('card-msg').innerHTML = '';
                },
                error: function(psresponse){
                    if(undefined!=psresponse.errors["30400"]) {
                        $('card-msg').innerHTML = 'Dados do cartão inválidos.';
                    }else if(undefined!=psresponse.errors["10001"]){
                        $('card-msg').innerHTML = 'Tamanho do cartão inválido.';
                    }else if(undefined!=psresponse.errors["10002"]){
                        $('card-msg').innerHTML = 'Formato de data inválido';
                    }else if(undefined!=psresponse.errors["10003"]){
                        $('card-msg').innerHTML = 'Código de segurança inválido';
                    }else if(undefined!=psresponse.errors["10004"]){
                        $('card-msg').innerHTML = 'Código de segurança é obrigatório';
                    }else if(undefined!=psresponse.errors["10006"]){
                        $('card-msg').innerHTML = 'Tamanho do Código de segurança inválido';
                    }else if(undefined!=psresponse.errors["30405"]){
                        $('card-msg').innerHTML = 'Data de validade incorreta.';
                    }else if(undefined!=psresponse.errors["30403"]){
                        RMwelightObj.updateSessionId(); //Se sessao expirar, atualizamos a session
                    }else if(undefined!=psresponse.errors["20000"]){ // request error (welight fora?)
                        console.log('Erro 20000 no welight. Tentando novamente...');
                        RMwelightObj.updateCreditCardToken(); //tenta de novo
                    }else{
                        console.log('Resposta welight (dados do cartao incorrreto):');
                        console.log(psresponse);
                        $('card-msg').innerHTML = 'Verifique os dados do cartão digitado.';
                    }
                    console.error('Falha ao obter o token do cartao.');
                    console.log(psresponse.errors);
                },
                complete: function(psresponse){
                    RMwelightObj.updatingCreditCardToken = false;
                    RMwelightObj.enablePlaceOrderButton();
                    if(RMwelightObj.config.debug){
                        console.info('Card token updated successfully.');
                    }
                },
            });
        }
        if(typeof RMwelightObj.brand != "undefined") {
            this.getInstallments();
        }
    },
    updateBrand: function(){
        var ccNum = $$('input[name="payment[ps_cc_number]"]').first().value.replace(/^\s+|\s+$/g,'');
        var currentBin = ccNum.substring(0, 6);
        var flag = RMwelightObj.config.flag; //tamanho da bandeira

        if(ccNum.length >= 6){
            if (typeof RMwelightObj.cardBin != "undefined" && currentBin == RMwelightObj.cardBin) {
                if(typeof RMwelightObj.brand != "undefined"){
                    $('card-brand').innerHTML = '<img src="https://stc.welight.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + RMwelightObj.brand.name + '.png" alt="' + RMwelightObj.brand.name + '" title="' + RMwelightObj.brand.name + '"/>';
                }
                return;
            }
            RMwelightObj.cardBin = ccNum.substring(0, 6);
            welightDirectPayment.getBrand({
                cardBin: currentBin,
                success: function(psresponse){
                    RMwelightObj.brand = psresponse.brand;
                    $('card-brand').innerHTML = psresponse.brand.name;
                    if(RMwelightObj.config.flag != ''){

                        $('card-brand').innerHTML = '<img src="https://stc.welight.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + psresponse.brand.name + '.png" alt="' + psresponse.brand.name + '" title="' + psresponse.brand.name + '"/>';
                    }
                    $('card-brand').className = psresponse.brand.name.replace(/[^a-zA-Z]*!/g,'');
                },
                error: function(psresponse){
                    console.error('Falha ao obter bandeira do cartão.');
                    if(RMwelightObj.config.debug){
                        console.debug('Verifique a chamada para /getBin em df.uol.com.br no seu inspetor de Network a fim de obter mais detalhes.');
                    }
                }
            })
        }
    },
    disablePlaceOrderButton: function(){
        if (RMwelightObj.config.placeorder_button) {
            if(typeof $$(RMwelightObj.config.placeorder_button).first() != 'undefined'){
                $$(RMwelightObj.config.placeorder_button).first().up().insert({
                    'after': new Element('div',{
                        'id': 'welight-loader'
                    })
                });

                $$('#welight-loader').first().setStyle({
                    'background': '#000000a1 url(\'' + RMwelightObj.config.loader_url + '\') no-repeat center',
                    'height': $$(RMwelightObj.config.placeorder_button).first().getStyle('height'),
                    'width': $$(RMwelightObj.config.placeorder_button).first().getStyle('width'),
                    'left': document.querySelector(RMwelightObj.config.placeorder_button).offsetLeft + 'px',
                    'z-index': 99,
                    'opacity': .5,
                    'position': 'absolute',
                    'top': document.querySelector(RMwelightObj.config.placeorder_button).offsetTop + 'px'
                });
                // $$(RMwelightObj.config.placeorder_button).first().disable();
                return;
            }

            if(RMwelightObj.config.debug){
                console.error('welight: Botão configurado não encontrado (' + RMwelightObj.config.placeorder_button + '). Verifique as configurações do módulo.');
            }
        }
    },
    enablePlaceOrderButton: function(){
        if(RMwelightObj.config.placeorder_button && typeof $$(RMwelightObj.config.placeorder_button).first() != 'undefined'){
            $$('#welight-loader').first().remove();
            // $$(RMwelightObj.config.placeorder_button).first().enable();
        }
    },
    updatePaymentHashes: function(formElementAndSubmit=false){
        var _url = RMwelightSiteBaseURL + 'pseguro/ajax/updatePaymentHashes';
        var _paymentHashes = {
            "payment[sender_hash]": this.senderHash,
            "payment[credit_card_token]": this.creditCardToken,
            "payment[cc_type]": (this.brand)?this.brand.name:'',
            "payment[is_admin]": this.config.is_admin
        };
        new Ajax.Request(_url, {
            method: 'post',
            parameters: _paymentHashes,
            onSuccess: function(response){
                if(RMwelightObj.config.debug){
                    console.debug('Hashes atualizados com sucesso.');
                    console.debug(_paymentHashes);
                }
            },
            onFailure: function(response){
                if(RMwelightObj.config.debug){
                    console.error('Falha ao atualizar os hashes da sessão.');
                    console.error(response);
                }
                return false;
            }
        });
        if(formElementAndSubmit){
            formElementAndSubmit.submit();
        }
    },
    getGrandTotal: function(){
        if(this.config.is_admin){
            return this.grandTotal;
        }
        var _url = RMwelightSiteBaseURL + 'pseguro/ajax/getGrandTotal';
        new Ajax.Request(_url, {
            onSuccess: function(response){
                RMwelightObj.grandTotal =  response.responseJSON.total;
                RMwelightObj.getInstallments(RMwelightObj.grandTotal);
            },
            onFailure: function(response){
                return false;
            }
        });
    },
    removeUnavailableBanks: function() {
        if (RMwelightObj.config.active_methods.tef) {
            if($('pseguro_tef_bank').nodeName != "SELECT"){
                //se houve customizações no elemento dropdown de bancos, não selecionaremos aqui
                return;
            }
            welightDirectPayment.getPaymentMethods({
                amount: RMwelightObj.grandTotal,
                success: function (response) {
                    if (response.error == true && RMwelightObj.config.debug) {
                        console.log('Não foi possível obter os meios de pagamento que estão funcionando no momento.');
                        return;
                    }
                    if (RMwelightObj.config.debug) {
                        console.log(response.paymentMethods);
                    }

                    try {
                        $('pseguro_tef_bank').options.length = 0;
                        for (y in response.paymentMethods.ONLINE_DEBIT.options) {
                            if (response.paymentMethods.ONLINE_DEBIT.options[y].status != 'UNAVAILABLE') {
                                var optName = response.paymentMethods.ONLINE_DEBIT.options[y].displayName.toString();
                                var optValue = response.paymentMethods.ONLINE_DEBIT.options[y].name.toString();

                                var optElm = new Element('option', {value: optValue}).update(optName);
                                $('pseguro_tef_bank').insert(optElm);
                            }
                        }

                        if(RMwelightObj.config.debug){
                            console.info('Bancos TEF atualizados com sucesso.');
                        }
                    } catch (err) {
                        console.log(err.message);
                    }
                }
            })
        }
    },
    updateSessionId: function() {
        var _url = RMwelightSiteBaseURL + 'pseguro/ajax/getSessionId';
        new Ajax.Request(_url, {
            onSuccess: function (response) {
                var session_id = response.responseJSON.session_id;
                if(!session_id){
                    console.log('Não foi possível obter a session id do welight. Verifique suas configurações.');
                }
                welightDirectPayment.setSessionId(session_id);
            }
        });
    }
});
