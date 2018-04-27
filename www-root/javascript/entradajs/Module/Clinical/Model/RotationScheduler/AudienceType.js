/**
 * AudienceType.js
 * @author Scott Gibson
 */

module.exports = class AudienceType
{
    static get User() {
        return 'proxy_id';
    }

    static get Group() {
        return 'group_id';
    }
};