var DisableEditing = function (context) {
    // Attach to summernote events when initialized
    this.events = {
        // This will be called when user releases a key on editable.
        'summernote.keydown': function (we, e) {
            e.preventDefault();

            return false;
        }
    };
};