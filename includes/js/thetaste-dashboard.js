$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

let newPortalDiv = document.querySelector('.new_port');
const mediaQuery = window.matchMedia('(min-width: 450px)');
const mediaQuery2 = window.matchMedia('(min-width: 1200px)');

window.addEventListener('load', (event) => {

    if(mediaQuery.matches){
        newPortalDiv.setAttribute('class', 'col-sm-4 col-md-4 dashboard_grid_cols d-flex justify-content-center align-items-center flex-column animate__animated animate__bounceInRight new_port');
    }

    if(mediaQuery2.matches) {
        newPortalDiv.setAttribute('class', 'col-sm-8 col-md-8 dashboard_grid_cols d-flex justify-content-center align-items-center flex-column animate__animated animate__bounceInRight new_port');
    }
});