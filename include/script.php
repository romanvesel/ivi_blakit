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
    $("#send_form").click(function(e){
        e.preventDefault();
        var $form = $(this).parents("form");
        $.ajax({
            url: '/ajax/send.php',
            method: 'post',
            data: $form.serializeArray(),
            success: function(data){

                if(data.includes("ok")){
                    modalOpen(modalAccess);
                    setTimeout(function () {
                        modalClose(modalAccess);
                    },5000);
                }
                else {
                    modalOpen(modalFail);
                }
            }
        });
    });
</script>