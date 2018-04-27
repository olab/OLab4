/**
 * LayoutManager.js
 * @author Scott Gibson
 */

const DateInterval = use('./../DateInterval');
const Rectangle = use('./Rectangle');

module.exports = class LayoutManager
{
    constructor(incrementWidth, maxHeight, minStartDate) {
        this.incrementWidth = incrementWidth;
        this.maxHeight = maxHeight;
        this.minStartDate = minStartDate;
    }

    positionShapeX(shape, startDate, endDate) {
        let startDifference = new DateInterval(this.minStartDate, startDate);
        let startOffset = Math.round(startDifference.days) * this.incrementWidth;
        let interval = new DateInterval(startDate, endDate);

        shape.point1.x = startOffset;
        shape.point2.x = shape.x1 + Math.floor(interval.days + 1) * this.incrementWidth;
    }

    positionShapeY(shape, overlapCount = 1, y = 0) {
        let height = this.maxHeight / overlapCount;

        shape.point1.y = y;
        shape.point2.y = shape.point1.y + height;
    }

    findHighestY2InRange(audiences, x1, x2) {
        let highestY2 = 0;

        for(let audience of audiences) {
            if(audience.shape.x1 >= x1 && audience.shape.x2 <= x2) {
                if(audience.shape.y2 > highestY2) {
                    highestY2 = audience.shape.y2;
                }
            }
        }

        return highestY2;
    }

    audiencesOverlap(audience1, audience2) {
        return audience1.shape.x1 > audience2.shape.x1 && audience1.shape.x1 < audience2.shape.x2
            || audience1.shape.x2 > audience2.shape.x1 && audience1.shape.x2 < audience2.shape.x2
            || audience1.shape.x1 <= audience2.shape.x1 && audience1.shape.x2 >= audience2.shape.x2
            || audience1.shape.x1 > audience2.shape.x1 && audience1.shape.x2 < audience2.shape.x2;
    }

    findOverlappingAudiences(audience, audiences) {
        let overlaps = [];

        for(let workingAudience of audiences) {
            if(this.audiencesOverlap(audience, workingAudience)) {
                overlaps.push(workingAudience);
            }
        }

        return overlaps;
    }

    countAudiencesInRange(audiences, x1, x2) {
        let overlapCount = 0;

        for(let audience of audiences) {
            if(x1 > audience.shape.x1 && x1 < audience.shape.x2
                || x2 > audience.shape.x1 && x2 < audience.shape.x2
                || x1 <= audience.shape.x1 && x2 >= audience.shape.x2
                || x1 > audience.shape.x1 && x2 < audience.shape.x2
            ) {
                overlapCount += 1;
            }
        }

        return overlapCount;
    }

    findShortestAudience(audiences) {
        let shortest = audiences[0];

        for(let audience of audiences) {
            if(audience.interval.milliseconds < shortest.interval.milliseconds) {
                shortest = audience;
            }
        }

        return shortest;
    }

    arrangeAudiences(audiences) {
        audiences.sort((a, b) => {
            return a.interval.milliseconds - b.interval.milliseconds;
        });

        audiences.map(audience => {
            audience.shape = Rectangle.create();
        });

        for(let audience of audiences) {
            this.positionShapeX(audience.shape, audience.startDate, audience.endDate);
        }

        for(let audience of audiences) {
            let overlappingAudiences = this.findOverlappingAudiences(audience, audiences);
            let shortestAudience = this.findShortestAudience(overlappingAudiences);
            let highestOverlaps = this.countAudiencesInRange(overlappingAudiences, shortestAudience.shape.x1, shortestAudience.shape.x2);
            let highestY2 = this.findHighestY2InRange(audiences, audience.shape.x1, audience.shape.x2);

            this.positionShapeY(audience.shape, highestOverlaps, highestY2);
        }
    }
};