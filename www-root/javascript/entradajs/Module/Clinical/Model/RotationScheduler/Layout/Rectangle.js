/**
 * Rectangle.js
 * @author Scott Gibson
 */

const Point = use('./Point');

module.exports = class Rectangle
{
    constructor(topLeftPoint, bottomRightPoint) {
        if(topLeftPoint instanceof Point === false) {
            throw new TypeError('Rectangle expects its first parameter to be a Point object.');
        }

        if(bottomRightPoint instanceof Point === false) {
            throw new TypeError('Rectangle expects its second parameter to be a Point object.');
        }

        this.point1 = topLeftPoint;
        this.point2 = bottomRightPoint;
    }

    static create(x1 = 0, y1 = 0, x2 = 0, y2 = 0) {
        return new Rectangle(new Point(x1, y1), new Point(x2, y2));
    }

    get x1() {
        return this.point1.x;
    }

    set x1(val) {
        this.point1.x = val;
    }

    get x2() {
        return this.point2.x;
    }

    set x2(val) {
        this.point2.x = val;
    }

    get y1() {
        return this.point1.y;
    }

    set y1(val) {
        this.point1.y = val;
    }

    get y2() {
        return this.point2.y;
    }

    set y2(val) {
        this.point2.y = val;
    }

    get width() {
        return this.point2.x - this.point1.x;
    }

    get height() {
        return this.point2.y - this.point1.y;
    }

    get area() {
        return this.width * this.height;
    }
};