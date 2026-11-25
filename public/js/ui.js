(function(){
  const region=document.createElement("div");
  region.className="toast-region";
  region.setAttribute("aria-live","polite");
  region.setAttribute("aria-atomic","true");
  document.body.appendChild(region);

  window.UI={
    announce(message){
      region.textContent="";
      window.setTimeout(()=>{region.textContent=message;},10);
    },
    toast(message,type="success"){
      const item=document.createElement("div");
      item.className="toast "+type;
      item.setAttribute("role",type==="error"?"alert":"status");
      item.textContent=message;
      region.appendChild(item);
      setTimeout(()=>item.remove(),4000);
    },
    loadingButton(button,label="Submitting..."){
      if(!button||button.dataset.loading==="1") return ()=>{};
      button.dataset.loading="1";
      const original=button.innerHTML;
      button.disabled=true;
      button.innerHTML='<span aria-hidden="true">◌</span> '+label;
      return ()=>{
        button.disabled=false;
        button.innerHTML=original;
        delete button.dataset.loading;
      };
    },
    async submitWithDelay(form,button){
      const restore=this.loadingButton(button,"Submitting...");
      await new Promise(resolve=>setTimeout(resolve,600));
      return ()=>{restore();form.submit();};
    },
    makeSortable(table){
      if(!table)return;
      table.querySelectorAll("thead th").forEach((header,index)=>{
        if(header.dataset.sortDisabled==="true")return;
        header.setAttribute("tabindex","0");
        header.setAttribute("role","button");
        header.style.cursor="pointer";
        header.dataset.sortDirection="none";
        const sort=()=>{
          const direction=header.dataset.sortDirection==="asc"?"desc":"asc";
          table.querySelectorAll("thead th").forEach(th=>{th.dataset.sortDirection="none";th.setAttribute("aria-sort","none");th._arrow && th._arrow.remove();});
          header.dataset.sortDirection=direction;
          header.setAttribute("aria-sort",direction==="asc"?"ascending":"descending");
          const arrow=document.createElement("span");arrow.textContent=direction==="asc"?" ▲":" ▼";header._arrow=arrow;header.appendChild(arrow);
          const tbody=table.tBodies[0];
          const rows=[...tbody.querySelectorAll("tr")].filter(row=>row.querySelectorAll("td").length);
          const type=header.dataset.sortType||"text";
          rows.sort((a,b)=>{
            const av=a.cells[index]?.getAttribute("data-sort")??a.cells[index]?.textContent.trim()??"";
            const bv=b.cells[index]?.getAttribute("data-sort")??b.cells[index]?.textContent.trim()??"";
            let cmp;
            if(type==="number")cmp=Number(av||0)-Number(bv||0);
            else if(type==="date")cmp=new Date(av||0)-new Date(bv||0);
            else cmp=av.localeCompare(bv,undefined,{sensitivity:"base",numeric:true});
            return direction==="asc"?cmp:-cmp;
          });
          rows.forEach(row=>tbody.appendChild(row));
          this.announce("Table sorted "+(direction==="asc"?"ascending":"descending")+" by "+header.textContent.replace(/[▲▼]/g,"").trim()+".");
        };
        header.addEventListener("click",sort);
        header.addEventListener("keydown",event=>{if(event.key==="Enter"||event.key===" "){event.preventDefault();sort();}});
      });
    },
    trapFocus(container){
      const focusable=()=>[...container.querySelectorAll('button:not([disabled]),a[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex="0"]')].filter(el=>el.offsetParent!==null);
      const previous=document.activeElement;
      const first=focusable()[0];
      if(first)first.focus();
      container.addEventListener("keydown",event=>{
        if(event.key==="Escape"){
          if(typeof container.close==="function")container.close();
          else container.classList.remove("open");
          if(previous&&typeof previous.focus==="function")previous.focus();
        }
        if(event.key==="Tab"){
          const items=focusable();
          if(!items.length)return;
          const first=items[0],last=items[items.length-1];
          if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
          else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
        }
      });
      if(typeof container.showModal==="function"){
        container.addEventListener("close",()=>previous?.focus());
      }
    }
  };

  document.addEventListener("DOMContentLoaded",()=>{
    document.querySelectorAll("table[data-sortable]").forEach(table=>window.UI.makeSortable(table));
    document.querySelectorAll("dialog,.modal-back").forEach(panel=>window.UI.trapFocus(panel));
    document.querySelectorAll("input[required],select[required],textarea[required]").forEach(field=>{
      const label=document.querySelector('label[for="'+CSS.escape(field.id)+'"]');
      if(label&&!/\*\s*$/.test(label.textContent)&&field.id)label.append(document.createTextNode(" *"));
    });
  });

  document.addEventListener("click",event=>{
    const open=event.target.closest("[data-open]");
    if(open){
      const panel=document.getElementById(open.dataset.open);
      if(panel){
        if(typeof panel.showModal==="function"){panel.showModal();}
        else panel.classList.add("open");
      }
    }
    const close=event.target.closest("[data-close]");
    if(close){
      const panel=close.closest("dialog,.modal-back");
      if(panel&&typeof panel.close==="function")panel.close();
      else if(panel)panel.classList.remove("open");
    }
    const toggle=event.target.closest("[data-password-toggle]");
    if(!toggle)return;
    const input=document.getElementById(toggle.dataset.passwordToggle);
    if(!input)return;
    const show=input.type==="password";
    input.type=show?"text":"password";
    toggle.textContent=show?"Hide":"Show";
    toggle.setAttribute("aria-label",(show?"Hide":"Show")+" password");
    toggle.setAttribute("aria-pressed",show?"true":"false");
  });

  document.querySelectorAll("form").forEach(form=>{
    const serverValidation=form.hasAttribute("data-server-validation");
    if(!serverValidation){
      form.querySelectorAll("input[required],select[required],textarea[required]").forEach(field=>{
        field.addEventListener("blur",()=>{
          if(field.checkValidity())return;
          const message=field.validationMessage||"This field is required.";
          let error=field.parentElement?.querySelector("[data-field-error]");
          if(!error){
            error=document.createElement("div");
            error.className="error show";
            error.setAttribute("role","alert");
            error.dataset.fieldError="1";
            field.insertAdjacentElement("afterend",error);
          }
          error.textContent=message;
          window.UI.announce(message);
        });
        field.addEventListener("input",()=>{
          const error=field.parentElement?.querySelector("[data-field-error]");
          if(field.checkValidity()&&error)error.remove();
        });
      });
    }
    form.addEventListener("submit",event=>{
      const button=form.querySelector('[type="submit"]:not([data-no-loading])');
      if(!button||button.dataset.loading==="1")return;
      event.preventDefault();
      const restore=window.UI.loadingButton(button);
      setTimeout(()=>{
        restore();
        if(serverValidation||form.checkValidity()){HTMLFormElement.prototype.submit.call(form);}
        else form.reportValidity();
      },600);
    });
  });
})();
