<script>
    function phone_mask(){
        $.mask.definitions['9']='';
        $.mask.definitions['d']='[0-9]';
        $("input[name=phone],input.phone").mask("(dd) ddd-dd-dd");
        $("input[name=phone],input.phone").intlTelInput({
            autoHideDialCode:false,
            autoPlaceholder:"aggressive",
            placeholderNumberType:"MOBILE",
            //preferredCountries:['by','ru'],
            separateDialCode:true,
            utilsScript:"<?=SITE_TEMPLATE_PATH?>/assets/utils.js",
            customPlaceholder:function(selectedCountryPlaceholder,selectedCountryData){
                return '+'+selectedCountryData.dialCode+' '+selectedCountryPlaceholder.replace(/[0-9]/g,'_');
            },
            //allowDropdown:false,
            //dropdownContainer:document.body,
            //excludeCountries:["us"],
            //formatOnDisplay:false,
            geoIpLookup:function(callback){
                $.get("http://ipinfo.io",function(){},"jsonp").always(function(resp){
                    var countryCode =(resp&&resp.country)?resp.country:"";
                    callback(countryCode);
                });
            },
            hiddenInput:"full_number",
            //initialCountry:"auto",
            //localizedCountries:{'de':'Deutschland'},
            //nationalMode:false,
            onlyCountries:['by','ru'],
        });
        $("input[name=phone],input.phone").on("close:countrydropdown",function(e,countryData){
            $(this).val('');
            //var mask=$(this).closest('.intl-tel-input').find('.selected-dial-code').html()+' '+$(this).attr('placeholder').replace(/[0-9]/g,'d');
            $(this).mask($(this).attr('placeholder').replace(/[_]/g,'d'));
        });
    }
    $("input[name=phone]").focus(function () {
        var input = this;
        setTimeout(function() {
            input.setSelectionRange(0, 0);
        }, 0);
    })
    phone_mask()
</script>
