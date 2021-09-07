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
    <div class="modal" id="modal__fail">
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
        $("input[name=phone],input.phone").mask("+375-(dd) ddd-dd-dd");
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

<script>

        let unlock = true;
        const timeout = 200;
        const lockPadding = document.querySelectorAll(".lock-padding");
        const body = document.querySelector("body");
        const modalAccess = document.querySelector("#modal__access");
        const modalFail = document.querySelector("#modal__fail");

        const modalOpen = (currentModal) => {
        if (currentModal && unlock) {
        const modalOpen = document.querySelector(".modal.--open");
        if (modalOpen) {
        modalClose(modalOpen, false);
    } else {
        bodyLock();
    }
        currentModal.classList.add("--open");
        currentModal.addEventListener("click", (e) => {
        const _this = e.currentTarget;
        if (!e.target.closest(".modal__content")) {
        modalClose(e.target.closest(".modal"));
    }
    });
    }
    };

        const modalClose = (modalOpen, doUnlock = true) => {
        if (unlock) {
        modalOpen.classList.remove("--open");
        if (doUnlock) {
        bodyUnlock();
    }
    }
    };

        const bodyLock = () => {
        const lockPaddingValue =
        window.innerWidth - document.querySelector("body").offsetWidth + "px";
        if (lockPadding.length > 0) {
        lockPadding.forEach((el) => {
        el.style.paddingRight = lockPaddingValue;
    });
    }

        body.style.paddingRight = lockPaddingValue;
        body.classList.add("--fixed");

        unlock = false;
        setTimeout(() => {
        unlock = true;
    }, timeout);
    };
        const bodyUnlock = () => {
        setTimeout(() => {
            if (lockPadding.length > 0) {
                lockPadding.forEach((el) => {
                    el.style.paddingRight = "0px";
                });
            }
            body.style.paddingRight = "0px";
            body.classList.remove("--fixed");
        }, timeout);
        unlock = false;
        setTimeout(() => {
        unlock = true;
    }, timeout);
    };
        // modalOpen(modalAccess); // вызов при удачном отправлении
        // modalOpen(modalFail); // вызов при не удачном отправлении
</script>

<script>
    $(document).ready(function() {
        $("form").on('submit', function(event){
            event.preventDefault();
            var th = $(this);
            $.ajax({
                type: "POST",
                url: "./ajax/send.php",
                data: th.serialize() ,
                success: function(data) {
                if(data.includes("success")){

                    modalOpen(modalAccess);

                }else if(!data){

                    modalOpen(modalFail);

                }
            }
          })
            // }).done(function(data) {
            //     if(data.includes("success")){

            //         modalOpen(modalAccess);

            //     }else if(!data){

            //         modalOpen(modalFail);

            //     }
            // });
           /* modalClose(modalFail);
            return false;*/
        });
    });
      /*  $(document).ready(function() {
          $("form").on('submit', function(event){
            event.preventDefault();
            var th = $(this);
            $.ajax({
              type: "POST",
              url: "./ajax/send.php",
              data: th.serialize()
            }).done(function() {
              modalOpen(modalAccess);
              setTimeout(function() {
                modalClose(modalAccess);
                th.trigger("reset");
              }, 2000);
            });
            modalClose(modalFail);
            return false;
          });
        });*/
    //     $("#send_form").click(function(e){
    //     e.preventDefault();
    //     var $form = $(this).parents("form");
    //     $.ajax({
    //     url: '/ajax/send.php',
    //     method: 'post',
    //     async:false,
    //     data: $form.serializeArray(),
    //     success: function(data){

    //     if(data.includes("ok")){
    //     modalOpen(modalAccess);
    //     setTimeout(function () {
    //     modalClose(modalAccess);
    // },5000);
    // }
    //     else {
    //     modalOpen(modalFail);
    // }
    // }
    // });
    // });
</script>

</body>
</html>