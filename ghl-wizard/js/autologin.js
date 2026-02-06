(function () {
    function autoLogin() {
      const params = new URLSearchParams(window.location.search);
      if (!params.has('lcw_auto_login')) return;
  
      const body = new URLSearchParams({
        action: 'lcw_auto_login_ajax',
        email: params.get('email') || '',
        redirect_to: params.get('redirect_to') || '',
        lcw_auth_key: params.get('lcw_auth_key') || '',
        first_name: params.get('first_name') || '',
        last_name: params.get('last_name') || '',
        id: params.get('id') || '',
        set_tags: params.get('set_tags') || '',
        remove_tags: params.get('remove_tags') || '',
        success_message: params.get('success_message') || 'You have been successfully logged in.'
      });
  
      fetch(hlwpw_ajax.ajax_url, { method: 'POST', body })
        .then(res => res.json())
        .then(response => {
          if (response.success) {
            window.location.href = (response.data && response.data.redirect) || '/';
          }
          const message = response.data && response.data.message;
          if (!message) return;
          const div = document.createElement('div');
          div.className = response.success ? 'lcw-auth-success-message' : 'lcw-auth-error-message';
          div.textContent = message;
          document.body.prepend(div);
          setTimeout(() => {
            if (div.isConnected) {
              div.remove();
            }
          }, 3000);
        })
        .catch(error => console.error('Autologin error:', error));
    }
  
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', autoLogin, { once: true });
    } else {
      autoLogin();
    }
  })();