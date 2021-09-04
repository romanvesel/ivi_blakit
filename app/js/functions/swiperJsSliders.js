// import Swiper from "swiper";
import Swiper from "../vendor/swiper-bundle.min";
const swiperJsSliders = () => {
  let collectionSlider = new Swiper(".collection-slider", {
    slidesPerView: "auto",
    spaceBetween: 30,
    autoplay: {
      enabled: true,
      delay: 0,
    },
    speed: 6000,
    navigation: {
      nextEl: ".collection-slider__next",
      prevEl: ".collection-slider__prev",
    },
    loop: true,
    centeredSlides: true,
    breakpoints: {
      320: {
        spaceBetween: 28,
      },
      640: {
        spaceBetween: 30,
      },
      1024: {
        spaceBetween: 30,
      },
      1280: {
        spaceBetween: 30,
      },
    },
    grabCursor: true,
    resizeObserver: true,
    a11y: {
      paginationBulletMessage: "Перейти к слайду {{index}}",
      prevSlideMessage: "Предыдущий слайд",
      nextSlideMessage: "Следующий слайд",
    },
  });

  let prizeSlider = new Swiper(".prize-slider", {
    slidesPerView: 1,
    spaceBetween: 15,
    grabCursor: true,
    resizeObserver: true,
    centeredSlides: true,
    grabCursor: true,
    effect: "creative",
    creativeEffect: {
      prev: {
        shadow: false,
        scale: 0.8,
        origin: "left center",
        translate: ["-90%", 0, 50],
        opacity: 0,
      },
      next: {
        shadow: false,
        scale: 0.8,
        origin: "left center",
        translate: ["90%", 0, 50],
        opacity: 0,
      },
    },
    navigation: {
      nextEl: ".prize-slider__btn-next",
      prevEl: ".prize-slider__btn-prev",
    },
    speed: 1300,
    loop: false,
    a11y: {
      paginationBulletMessage: "Перейти к слайду {{index}}",
      prevSlideMessage: "Предыдущий слайд",
      nextSlideMessage: "Следующий слайд",
    },
  });
};
export default swiperJsSliders;
