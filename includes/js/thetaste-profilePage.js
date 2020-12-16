const pathname = window.location.pathname;
console.log(pathname);

window.addEventListener('load', () => {
   if(pathname === '/taste/venue-profile-page/' || pathname === '/venue-profile-page/' || pathname === '/venue-profile-page') {
       let body = document.body;
       body.classList.add('bodyScroll');
   }else {
       alert('error');
   }
});