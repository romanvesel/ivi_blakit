// import attrClear function
import attrClear from "./functions/attrClear";

// import modalWindow functions
import { modalClose } from "./functions/modalWindow";

// import modalWindow init functions
import modalWindowInit from "./functions/modalWindowInit";

// import  btns functions
import btnsFunc from "./functions/btns";

// import lazyLoading functions
import observer from "./functions/lazyLoading";

// import jquery
// import $ from "jquery";

// import intlTelInput from "intl-tel-input";
// import customSelectFunc functions
// import customSelectFunc from "./functions/customSelect";

// import tabsChange functions
// import tabsChange from "./functions/tabsChange";

// import collapsibleFunc function
// import collapsibleFunc from "./functions/collapsible";

// import lazyBg function
// import lazyBg from "./functions/lazyBg";

// import ytPlayer function
// import ytPlayer from "./functions/youtubePlayer";

// import menuDropdown function
// import menuDropdown from "./functions/menuDropdown";
//  import swiperJsSliders
import swiperJsSliders from "./functions/swiperJsSliders";
// import showVisible
import showVisible from "./functions/showVisible";
//setMarginTop function
import setMarginTop from "./functions/setMarginTop";
document.addEventListener("DOMContentLoaded", () => {
  // variable start
  // const phoneInput = document.querySelectorAll("input[type=tel]");
  const images = document.querySelectorAll("img");
  const phoneLink = document.querySelectorAll("a[href^='tel']");
  const burgerMenus = document.querySelectorAll(".menu__burger");
  const menu = document.querySelector(".menu-nav");
  const modalCloseIcons = document.querySelectorAll(".close__modal");
  const body = document.querySelector("body");
  const main = document.querySelector(".main");
  const breadcrumb = document.querySelector(".breadcrumb");
  const lazyImages = document.querySelectorAll(
    "img[data-lazy-src],source[data-lazy-srcset] "
  );
  const animateItems = document.querySelectorAll(".animate");
  const preloaderProgress = document.querySelector(".preloader__progress");
  // const phoneInputs = document.querySelectorAll("input[type=tel]");
  const showPassBtns = document.querySelectorAll(".show-pass");
  const copyrightYear = document.querySelectorAll(".current-year");
  const menuItems = document.querySelectorAll(".menu__item");

  // variable end

  // function call start
  // ytPlayer();
  // lazyBg();
  // modalWindowInit();
  btnsFunc();
  showVisible();
  window.onscroll = showVisible;
  swiperJsSliders();
  // menuDropdown();
  // customSelectFunc();
  // collapsibleFunc();
  // tabsChange();
  // function call end
  // initialise plugin

  if (menuItems.length > 0) {
    menuItems.forEach((item) => {
      item.addEventListener("click", (e) => {
        let _this = e.currentTarget;
        let activeMenuItem = document.querySelector(".menu__item.__active");
        let menuOpen = document.querySelector(".menu-nav.--show");
        let burgerClicked = document.querySelectorAll(
          ".menu__burger.--clicked"
        );
        burgerClicked.length > 0
          ? burgerClicked.forEach((element) => {
              element.classList.remove("--clicked");
            })
          : "";
        menuOpen ? menuOpen.classList.remove("--show") : "";
        activeMenuItem.classList.remove("__active");
        _this.classList.toggle("__active");
      });
    });
  }
  if (copyrightYear.length > 0) {
    const year = new Date().getFullYear();
    copyrightYear.forEach((item) => {
      item.innerHTML = year;
    });
    copyrightYear.innerHTML = year;
  }
  if (showPassBtns.length > 0) {
    showPassBtns.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        let _this = e.currentTarget;
        let attr = _this.parentNode.querySelector("input").getAttribute("type");
        attr === "password"
          ? (_this.parentNode
              .querySelector("input")
              .setAttribute("type", "text"),
            (_this.style.opacity = 1))
          : (_this.parentNode
              .querySelector("input")
              .setAttribute("type", "password"),
            (_this.style.opacity = 0.2));
      });
    });
  }
  // if (phoneInputs.length > 0) {
  //   phoneInputs.forEach((input) => {
  //     intlTelInput(input, {
  //       utilsScript: "../assets/utils.js",
  //       initialCountry: "by",
  //     });
  //   });
  // }

  setTimeout(() => {
    let body = document.querySelector("body");
    body.classList.add("__loading");
    body.classList.add("--fixed");
    for (let i = 0; i < 100; i++) {
      preloaderProgress.value++;
    }
    window.setTimeout(function () {
      body.classList.add("__load");
      body.classList.remove("__loading");
      body.classList.remove("--fixed");
    }, 700);
  }, 100);
  const setMainMarginTop = () => {
    if (main) {
      !main.classList.contains("mt__nan")
        ? setMarginTop("#header", ".main")
        : "";
    }
  };
  setMainMarginTop();
  //animate not scroll items
  if (animateItems.length > 0) {
    animateItems.forEach((item) => {
      if (!item.classList.contains("scroll")) {
        setInterval(() => {
          item.classList.add("__show");
        }, 1000);
      }
    });
  }
  //preventDefault last lastBreadcrumb item click
  if (breadcrumb) {
    let lastBreadcrumb = breadcrumb.lastElementChild;

    if (lastBreadcrumb) {
      lastBreadcrumb.addEventListener("click", (e) => {
        e.preventDefault();
      });
    }
  }

  // init modal close btn
  if (modalCloseIcons.length > 0) {
    modalCloseIcons.forEach((icon) => {
      icon.addEventListener("click", (e) => {
        modalClose(icon.closest(".modal"));
        e.preventDefault();
      });
    });
  }

  // call close popup func on ESC keypress
  document.addEventListener("keydown", function (e) {
    if (e.which === 27) {
      let modalOpen = document.querySelector(".modal.--open");
      let menuOpen = document.querySelector(".menu-nav.--show");

      modalOpen ? modalClose(modalOpen) : "";
      menuOpen ? menuOpen.classList.remove("--show") : "";
    }
  });

  // phone link clear white space
  if (phoneLink.length > 0) {
    phoneLink.forEach((link) => {
      attrClear(link, "href", 2);
    });
  }

  // images clear title and alt attribute
  if (images.length > 0) {
    images.forEach((img) => {
      attrClear(img, "title", 1);
      attrClear(img, "alt", 1);
    });
  }

  //init lazy loading images
  if (lazyImages.length > 0) {
    lazyImages.forEach((image) => {
      observer.observe(image);
    });
  }

  //  burgerMenu function
  if (burgerMenus) {
    burgerMenus.forEach((burgerMenu) => {
      burgerMenu.addEventListener("click", function (e) {
        e.stopPropagation();
        e.preventDefault();
        const clickedBurgerMenus = document.querySelectorAll(".menu__burger");
        clickedBurgerMenus.forEach((element) => {
          element.classList.contains("--clicked")
            ? element.classList.remove("--clicked")
            : element.classList.add("--clicked");
        });
        // burgerMenu.classList.toggle("--clicked");
        body.classList.toggle("--fixed");
        menu.classList.toggle("--show");
      });
    });
  }
  window.onresize = function () {
    setMainMarginTop();

    setTimeout(() => {
      setMainMarginTop();
    }, 1000);
  };
});
