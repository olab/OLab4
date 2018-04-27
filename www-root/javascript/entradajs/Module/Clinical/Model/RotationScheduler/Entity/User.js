/**
 * Audience.js
 * @author Eric Howarth
 */

module.exports = class User
{
    constructor(id, number, username, firstname, lastname, email, photo, level, audience) {
        this.id = id;
        this.number = number;
        this.username = username;
        this.firstname = firstname;
        this.lastname = lastname;
        this.email = email;
        this.photo = photo;
        this.level = level;
        this.audience = audience;
    }
};