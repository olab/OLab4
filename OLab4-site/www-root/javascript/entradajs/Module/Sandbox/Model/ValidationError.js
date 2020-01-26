/**
 * ValidationError.js
 * @author Scott Gibson
 */

module.exports = class ValidationError extends Error
{
    constructor(fieldName, ruleName, value) {
        super();

        this.fieldName = fieldName;
        this.ruleName = ruleName;
        this.value = value;
    }

    getFieldName() { return this.fieldName; }
    getRuleName() { return this.ruleName; }
    getValue() { return this.value; }
};