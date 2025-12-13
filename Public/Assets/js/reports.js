// reports.js - handles AJAX behavior for dayBills modal and actions (view bill, pagination, form submit)
(function(){
  function qs(id){ return document.getElementById(id); }

  document.addEventListener('DOMContentLoaded', function(){
    var content = qs('dayBillsContent');
    var overlay = qs('dayBillsOverlay');
    var openBtn = qs('openDayBillsBtn');
    var loadingHtml = '<div class="text-center p-4">Cargando...</div>';

    if(!content) return;

    // Delegated click handling inside injected content
    content.addEventListener('click', function(e){
      var btn = e.target.closest('.view-bill');
      if(btn){
        var id = btn.getAttribute('data-id');
        if(id){
          // Open invoice in new window (same behavior as existing Sales.js)
          window.open('factura.php?pg=bill&id=' + encodeURIComponent(id), '_blank', 'width=350,height=900');
        }
        return;
      }

      var pageLink = e.target.closest('.report-page-link');
      if(pageLink){
        e.preventDefault();
        var page = pageLink.dataset.page || '1';
        // find current form (if any) to build params
        var form = content.querySelector('#filtrosReporte');
        var params = new URLSearchParams();
        if(form){
          var formData = new FormData(form);
          for(var pair of formData.entries()) params.set(pair[0], pair[1]);
        }
        params.set('page', page);
        params.set('ajax','1');
        fetchResults('index.php?pg=reports&action=dayBills&' + params.toString());
      }
    });

    // Intercept form submit inside injected content to fetch results via AJAX
    content.addEventListener('submit', function(e){
      if(e.target && e.target.matches('#filtrosReporte')){
        e.preventDefault();
        var form = e.target;
        var params = new URLSearchParams(new FormData(form));
        params.set('ajax','1');
        // ensure pg and action present
        if(!params.get('pg')) params.set('pg','reports');
        if(!params.get('action')) params.set('action','dayBills');
        fetchResults('index.php?pg=reports&action=dayBills&' + params.toString());
      }
    });

    // If the modal is opened via header script, results are injected; ensure close behavior clears content
    window.closeDayBillsOverlay = window.closeDayBillsOverlay || function(event){
      if(!overlay) return;
      if(!event || (event && event.target && event.target.id === 'dayBillsOverlay')){
        overlay.classList.remove('active');
        try{ content.innerHTML = '<div id="dayBillsLoading" style="display:flex;align-items:center;justify-content:center;height:100%;">Cargando...</div>'; }catch(e){}
        document.removeEventListener('keydown', escHandler);
      }
    };

    function escHandler(e){ if(e.key === 'Escape' || e.key === 'Esc') closeDayBillsOverlay(); }

    function fetchResults(url){
      if(!content) return;
      content.innerHTML = loadingHtml;
      fetch(url, { credentials: 'same-origin' })
        .then(function(res){ if(!res.ok) throw new Error('Network response was not ok'); return res.text(); })
        .then(function(html){
          content.innerHTML = html;
          // ensure modal scroll to top
          content.scrollTop = 0;
        })
        .catch(function(err){
          content.innerHTML = '<div class="text-center text-danger p-3">Error cargando resultados</div>';
          console.error('reports.js fetch error:', err);
        });
    }

    // Expose fetchResults so initial header script can call it if desired
    window.reportsFetchResults = fetchResults;

  });
})();
