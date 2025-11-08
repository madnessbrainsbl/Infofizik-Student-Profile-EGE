globalThis.AdminPage = ((module) => {

  module.Init = () => {
    module.InitEventListeners();

    var firstCourse = $('.course-tab-content').get(0);
    if (firstCourse) {
      module.OpenCourseTab(firstCourse.dataset.course_id);
    }
  };

  module.InitEventListeners = () => {

    $('[data-action=OpenCourseTab]').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const courseId = this.dataset.course_id;
      module.OpenCourseTab(courseId);
    });
    
    $('[data-action=SaveCourseEgePoints]').on('click', function(){
      const $tabContent = $(this).closest('.course-tab-content__user-grades');

      var userGrades = {};
      $tabContent.find('input[name=grades]').each((idx, el) => {
        userGrades[el.dataset.user_id] = el.value;
      });

      var userWeeklyVariantCounts = {};
      $tabContent.find('input[name=weekly_variant_count]').each((idx, el) => {
        userWeeklyVariantCounts[el.dataset.user_id] = el.value;
      });

      post_api('save-course-ege-grades', {
        course_id: $tabContent.data('course_id'),
        grades: userGrades,
        weekly_variant_count: userWeeklyVariantCounts,
      }).then((response) => {
        if (response.ok) {
          // NOTE: do nothing
        } else {
          console.error(response);
        }
      });
    });

    $('[data-action=StartEditPointMap]').on('click', function(e){
      e.preventDefault();
      e.stopPropagation();

      this.classList.add('hidden');
      const $container = $(this).closest('.course-tab-content__course-point-map');
      $container.find('[data-action=SavePointMap]').removeClass('hidden');

      $container.find('table').removeClass('hidden');
    });

    $('[data-action=SavePointMap]').on('click', function(e){
      e.preventDefault();
      e.stopPropagation();

      this.classList.add('hidden');
      const $container = $(this).closest('.course-tab-content__course-point-map');
      $container.find('[data-action=StartEditPointMap]').removeClass('hidden');

      var map = {};
      $container.find('table input').each((idx, el) => {
        map[el.dataset.from_point] = el.value;
      });
      
      post_api('save-course-ege-map', {
        course_id: $container.data('course_id'),
        map: map
      }).then((response) => {
        if (response.ok) {
          $container.find('table').addClass('hidden');
        } else {
          console.error(response);
        }
      });
    });

    $('[data-action=ToggleTelegramConnectionStatus]').on('click', function(){
      var element = this;
      post_api('profile__toggle-bot-update-state', {
        course_id: this.dataset.course_id,
        user_id: this.dataset.user_id,
      }).then((response) => {
        if (response.ok) {
          element.innerHTML = response.msg;
        } else {
          console.error(response);
        }
      });
    });
  };

  module.OpenCourseTab = (courseId) => {
    $('.admin-course-nav a.nav-link').each((idx, element) => {

      if (element.dataset.course_id == courseId) {
        element.classList.add('active');
      } else {
        element.classList.remove('active');
      }
      
    });
    
    $('.course-tab-content').each((idx, element) => {

      if (element.dataset.course_id == courseId) {
        element.classList.remove('hidden');
      } else {
        element.classList.add('hidden');
      }
      
    });

    
  };
  
  return module;
})(globalThis.AdminPage || {});
