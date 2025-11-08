globalThis.AdminBotPage = ((module) => {

  let Toast = null;
  
  module.Init = (toastModule, page) => {
    Toast = toastModule;
    module.Page = page;
    
    module.InitEventListeners();
  };

  module.InitEventListeners = () => {
    module.Page.querySelector('[data-action=SaveBotSettings]').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      post_api('telegram-bot__save-settings', {
        bot_key: Form_BotKey.value,
        is_active: Form_BotIsActive.checked ? 'yes' : 'no'
      }).then((response) => {
        if (response.ok) {
          Toast.add('Данные сохранены успешно', {type: 'success'});
        } else {
          Toast.add('Возникла ошибка при сохранении. Ошибка: ' + response.msg, {type: 'danger'});
        }
      });
    });

    module.Page.querySelector('[data-action=SetBotWebhook]').addEventListener('click', function(e) {
      post_api('telegram-bot__set-bot-webhook', {}).then((response) => {
        if (response.ok) {
          Toast.add('Операция выполнена успешно', { type: 'success' });
        } else {
          Toast.add('При выполнении операции возникла ошибка: ' + response.msg, { type: 'danger' });
        }
      });
    });

    module.Page.querySelector('[data-action=SendTestMessage]').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      post_api('telegram-bot__send-test-message', {
        chat_id: Test_ChatId.value,
        message: Test_Message.value,
        user_id: Test_UserId.value,
        course_id: Test_CourseId.value,
      }).then((response) => {
        if (response.ok) {
          Toast.add('Операция выполнена успешно', { type: 'success' });
        } else {
          Toast.add('При выполнении операции возникла ошибка: ' + response.msg, { type: 'danger' });
        }
      });
    });

    module.Page.querySelector('[data-action=SaveBotAdministrators]').addEventListener('click', function(e) {
      post_api('telegram-bot__set-admin-list', {
        admins: Bot_BotAdmins.value
      }).then((response) => {
        if (response.ok) {
          Toast.add('Операция выполнена успешно', { type: 'success' });
        } else {
          Toast.add('При выполнении операции возникла ошибка: ' + response.msg, { type: 'danger' });
        }
      });
    });

    module.Page.querySelector('[data-action=RemoveWebhook]').addEventListener('click', function(e) {
      post_api('telegram-bot__delete-bot-webhook', {
      }).then((response) => {
        if (response.ok) {
          Toast.add('Операция выполнена успешно', { type: 'success' });
        } else {
          Toast.add('При выполнении операции возникла ошибка: ' + response.msg, { type: 'danger' });
        }
      });
    });

    module.Page.querySelector('[data-action=SetBotSchedule]').addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      
      var form = this.closest('form');

      if (form) {
        var dataToSend = {};
        dataToSend.time = form.querySelector('input[name=time]').value;
        dataToSend.days = [];

        form.querySelectorAll('input[type=checkbox]').forEach((el) => {
          if (el.checked) {
            dataToSend.days.push(el.value);
          }
        });


        post_api('telegram-bot__set-bot-schedule', dataToSend).then((response) => {
          if (response.ok) {
            Toast.add(response.msg, { type: 'success' });
          } else {
            Toast.add('Ошибка: ' + response.msg, { type: 'danger' });
          }
        });
        
      }
    });

    module.Page.querySelector('[data-action=InfoWebhook]').addEventListener('click', function(e) {
      post_api('telegram-bot__info-bot-webhook', {
      }).then((response) => {
        if (response.ok) {
          Toast.add(response.msg, { type: 'success' });
          console.info(response.msg);
        } else {
          Toast.add('При выполнении операции возникла ошибка: ' + response.msg, { type: 'danger' });
        }
      });
    });

    
    
  };
  
  return module;
})(globalThis.AdminBotPage || {});
