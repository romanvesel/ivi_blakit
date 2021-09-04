</main>
    <footer class="footer" id="contacts">
      <?
      $APPLICATION->IncludeFile(
        SITE_DIR."include/questions_about.php",
        Array(),
        Array("MODE"=>"html")
      );
      ?>

      <?
      $APPLICATION->IncludeFile(
        SITE_DIR."include/phone_email.php",
        Array(),
        Array("MODE"=>"html")
      );
      ?> 

      <?
      $APPLICATION->IncludeFile(
        SITE_DIR."include/copyright.php",
        Array(),
        Array("MODE"=>"html")
      );
      ?>
    </footer>
    <div class="modal" id="modal__access">
      <div class="modal__body __alert __alert-access">
        <div class="modal__content modal-alert __access"><a class="modal__close __alert close__modal" href="#" role="button"> <span></span><span></span></a>
                    <picture class="modal-alert__picture" itemscope itemtype="http://schema.org/ImageObject">
                      <source srcset="<?=SITE_TEMPLATE_PATH?>/img/icons/icon-access.svg" itemprop="contentUrl"><img class="img--full img--center modal-alert__image  " src="<?=SITE_TEMPLATE_PATH?>/img/icons/icon-access.svg" alt="Ваша заявка принята" title="Ваша заявка принята" draggable="false" loading="lazy" itemprop="contentUrl">
                    </picture>
          <article class="modal-alert__text">
            <h5 class="modal-alert__title">Ваша заявка принята</h5>
            <p>На указанный номер отправлено SMS с кодом активации</p>
          </article>
        </div>
      </div>
    </div>
    <div class="modal" id="modal__denied">
      <div class="modal__body __alert __alert-denied">
        <div class="modal__content modal-alert __denied"><a class="modal__close __alert close__modal" href="#" role="button"> <span></span><span></span></a>
                    <picture class="modal-alert__picture" itemscope itemtype="http://schema.org/ImageObject">
                      <source srcset="<?=SITE_TEMPLATE_PATH?>/img/icons/icon-denied.svg" itemprop="contentUrl"><img class="img--full img--center modal-alert__image  " src="<?=SITE_TEMPLATE_PATH?>/img/icons/icon-denied.svg" alt="Ваша заявка отклонена" title="Ваша заявка отклонена" draggable="false" loading="lazy" itemprop="contentUrl">
                    </picture>
          <article class="modal-alert__text">
            <h5 class="modal-alert__title">Ваша заявка отклонена</h5>
            <p>Проверьте правильно ли вы ввели код указанный на упаковке </p>
          </article>
        </div>
      </div>
    </div>
  <?$APPLICATION->AddHeadScript("/dist/assets/jquery-3.6.0.min.js", true);?>
  <?$APPLICATION->AddHeadScript("/dist/assets/intlTelInput-jquery.min.js", true);?>
  <?$APPLICATION->AddHeadScript("/dist/assets/jquery.maskedinput.min.js", true);?>
  <?$APPLICATION->AddHeadScript("/dist/js/main.min.js", true);?>

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

</body>
</html>