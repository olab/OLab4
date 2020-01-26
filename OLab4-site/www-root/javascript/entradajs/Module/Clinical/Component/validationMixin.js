/**
 * validationMixin.js
 * @author Scott Gibson
 */

const RestClient = use('EntradaJS/Http/RestClient');

module.exports = {
    data() {
        return {
            validation_errors: [],
        };
    },
    methods: {
        catchError(error) {
            // Failed response (status >= 400)
            // Check reason for failure
            switch(error.constructor) {
                // API rejected the request or threw an error (e.g. 500)
                case RestClient.Errors.RestError:
                    console.log('Caught RestError: ', error.response.json());
                    if(error.response.json()[0] === "validation_error") {
                        this.validation_errors = error.response.json()[2];
                    }
                    // Check the reason for rejection
                    if(error.response.status === 404) {
                        console.log('Page not found!');
                    }
                    else if(error.response.status === 500) {
                        console.log('The server threw an error.');
                    }

                    break;

                // HTTP request failed (e.g. could not connect to server)
                case RestClient.Errors.NetworkError:
                    console.log('Caught NetworkError: ', error);
                    break;
            }
        }
    }
};