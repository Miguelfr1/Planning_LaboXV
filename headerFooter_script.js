
     const burgerButton = document.querySelector("#burgerButton");
      const fullscreenMenu = document.querySelector("#fullscreenMenu");
      const body = document.querySelector("body");
      const menuClose = document.querySelector("#menuClose");

      burgerButton.addEventListener("click", () => {
          fullscreenMenu.classList.toggle("active");
          body.classList.toggle("menu-open");
          burgerButton.classList.toggle("active");
      });

      menuClose.addEventListener("click", () => {
          fullscreenMenu.classList.remove("active");
          body.classList.remove("menu-open");
          burgerButton.classList.remove("active");
      });

