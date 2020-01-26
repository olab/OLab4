function get_objective_text(objective, always_show_code) {
    if (objective['objective_code']) {
        return objective['objective_code'] + ': ' + objective['objective_name'];
    } else {
        var is_code = /^[A-Z]+\-[\d\.]+$/.test(objective['objective_name']);
        if (objective['objective_description'] && is_code) {
            if (always_show_code) {
                return objective['objective_name'] + ': ' + objective['objective_description'];
            } else {
                return objective['objective_description'];
            }
        } else {
            return objective['objective_name'];
        }
    }
}
