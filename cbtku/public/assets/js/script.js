// --- SLIDE ---
let current=0;const slides=document.querySelectorAll('.slide');
function changeSlide(dir){current=(current+dir+slides.length)%slides.length;showSlide();}
function showSlide(){slides.forEach((s,i)=>s.classList.toggle('active',i===current));}
setInterval(()=>changeSlide(1),5000);

// --- TESTIMONI AUTO ---
let testi=0;const t=document.querySelectorAll('.testimoni');
setInterval(()=>{t[testi].classList.remove('active');testi=(testi+1)%t.length;t[testi].classList.add('active');},6000);

function toggleMenu() {
  const navList = document.querySelector('nav ul');
  navList.classList.toggle('active');
}

document.querySelectorAll('nav a').forEach(link => {
  link.addEventListener('click', () => {
    const navList = document.querySelector('nav ul');
    navList.classList.remove('active');
  });
});
