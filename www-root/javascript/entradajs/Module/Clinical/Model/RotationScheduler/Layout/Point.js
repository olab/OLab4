/**
 * Point.js
 * @author Scott Gibson
 */

module.exports = class Point
{
    constructor(x, y, z) {
        this.x = Number(x);
        this.y = Number(y);
        this.z = Number(z);
    }

    set(x, y, z) {
        this.x = x;
        this.y = y;
        this.z = z;
    }

    translate(x_distance, y_distance, z_distance) {
        this.translateX(x_distance);
        this.translateY(y_distance);
        this.translateZ(z_distance);
    }

    translateX(distance) {
        this.x += distance;
    }

    translateY(distance) {
        this.y += distance;
    }

    translateZ(distance) {
        this.z += distance;
    }
};