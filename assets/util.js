globalThis.post_api = (action, data) => {
  const url = '/local/studentprofile/api.php';
  var promise = $.ajax({
    type: 'POST',
    url: url,
    //contentType: "application/json; charset=utf-8",
    dataType: 'json',
    data: {
      action: action,
      data: data,
    }
  });
  return promise;
};
