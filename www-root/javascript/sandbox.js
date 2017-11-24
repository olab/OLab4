
// Set defaults
axios.defaults.baseURL = API_URL + '/sandbox'
axios.defaults.headers.common["Authorization"] = 'Bearer ' + JWT

// Intercept response
axios.interceptors.response.use(function(response) {

  // If response has a new authorization header, replace it as default JWT
  if (response.headers.authorization) {
    axios.defaults.headers.common["Authorization"] = response.headers.authorization
  }

  return response
})

// Init Vue instance
var vm = new Vue({
  el: '#module-sandbox',

  data: {
    sandboxes: {
      total: 0,
      per_page: 0,
      current_page: 1,
      last_page: 0,
      next_page_url: null,
      prev_page_url: null,
      from: 0,
      to: 0,
      data: []
    },
    current_user_can: {
      read: false,
      create: false,
      update: false,
      delete: false
    },
    'new_sandbox': {
      title: '',
      description: ''
    },
    'update_sandbox': {
      id: 0,
      title: '',
      description: ''
    },
    form_errors: {
      title: '',
      description: ''
    }
  },  

  mounted: function() {
    this.getSandboxes()
  },

  watch: {
    
    // When entering new data, empty out form errors

    'new_sandbox.title': function (newValue) {
      this.form_errors.title = ''
    },

    'new_sandbox.description': function (newValue) {
      this.form_errors.description = ''
    },

    'update_sandbox.title': function (newValue) {
      this.form_errors.title = ''
    },

    'update_sandbox.description': function (newValue) {
      this.form_errors.description = ''
    },

  },

  methods: {
    
    getSandboxes: function(url) {
      var url = url ? url : axios.defaults.baseURL

      axios.get(url).then((response) => {
        this.sandboxes = Object.assign({}, response.data.sandboxes)
        this.current_user_can = Object.assign({}, response.data.current_user_can)
      })
    },

    getPage: function(page) {
      this.getSandboxes(axios.defaults.baseURL + '?page=' + page)
    },

    createSandbox: function() {
      var params = new URLSearchParams();
      params.append('title', this.new_sandbox.title)
      params.append('description', this.new_sandbox.description)

      axios.post('', params).then((response) => {
        this.getPage(this.sandboxes.current_page)

        this.new_sandbox = {
          title: '',
          description: ''
        }

        jQuery('#create-sandbox').modal('hide')
      })
      .catch(this.handleFormErrors)
    },

    editSandbox: function(sandbox) {
      this.update_sandbox.id = sandbox.id
      this.update_sandbox.title = sandbox.title
      this.update_sandbox.description = sandbox.description

      this.form_errors = {
        title: '',
        description: ''
      }

      jQuery("#edit-sandbox").modal('show');
    },

    updateSandbox: function(id) {
      var params = new URLSearchParams();
      params.append('title', this.update_sandbox.title)
      params.append('description', this.update_sandbox.description)

      axios.put('/' + id, params).then((response) => {
        this.getPage(this.sandboxes.current_page)

        this.update_sandbox = {
          id: 0,
          title: '',
          description: ''
        }

        jQuery("#edit-sandbox").modal('hide');
      })
      .catch(this.handleFormErrors)
    },

    deleteSandbox: function(id) {
      if (confirm('Are you sure you want to delete this sandbox?')) {
        axios.delete('/' + id).then((response) => {
          this.getPage(this.sandboxes.current_page)
        })
      }
    },

    handleFormErrors: function(error) {
      if (error.response && error.response.data.title) {
        this.form_errors.title = error.response.data.title[0]
      }

      if (error.response && error.response.data.description) {
        this.form_errors.description = error.response.data.description[0]
      }
    }
  },
})