/**
 * Fetches data from an Entrada API
 *
 * @param api: Name of API to call in the www-root/api/*.api.php (the * part is all you need), e.g. 'curriculum-periods'
 * @param type: 'GET', 'POST', etc.
 * @param method: Method name within the API, e.g. 'get-objectives'
 * @param params: Object containing params, e.g. {'org_id': 1, 'name': 'Dr. So-and-so'}
 * @return promise: Returns data when success, error message when error.
 */
function call_api(api, type, method, params) {
    var deferred = jQuery.Deferred();

    var base_url;
    if (typeof ENTRADA_URL !== 'undefined') {
        base_url = ENTRADA_URL;
    } else if (typeof SITE_URL !== 'undefined') {
        base_url = SITE_URL;
    }
    var url = base_url + '/api/' + api + '.api.php';
    var query = jQuery.extend(params, {'method': method});

    function parse(data) {
        var response;
        try {
            response = JSON.parse(data);
        } catch (error) {
            response = {'status': 'error', 'data': data};
        }
        return response;
    }

    function fail(message) {
        console.error(type, url, query, message);
        deferred.reject(message);
    }

    jQuery.ajax({
        url: url,
        type: type,
        data: query,
        success: function(response, status, xhr) {
            switch (response['status']) {
                case 'success':
                    deferred.resolve(response['data']);
                    break;
                case 'error':
                    fail(response['data']);
                    break;
                default:
                    fail(response);
                    break;
            }
        },
        error: function(xhr, status, error) {
            var response = parse(xhr.responseText);
            fail(response['data']);
        }
    });

    return deferred.promise();
}
