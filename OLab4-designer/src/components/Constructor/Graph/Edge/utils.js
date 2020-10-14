// @flow
import * as d3 from 'd3';
import { intersect, shape } from 'svg-intersections';
import { Point2D, Matrix2D } from 'kld-affine';
import { Intersection } from 'kld-intersections';

import { COLLAPSED_HEIGHT } from '../Node/config';
import { VARIANT, EDGE_HANDLE_SIZE } from './config';

import type { Node as NodeType } from '../Node/types';
import type {
  Edge as EdgeType,
  ITargetPosition as ITargetPositionType,
  IntersectResponse as IntersectResponseType,
} from './types';

export const getVariantValueDOM = (variant: number): string => {
  switch (variant) {
    case VARIANT.STANDARD:
      return VARIANT.STANDARD_DOM;
    case VARIANT.DASHED:
      return VARIANT.DASHED_DOM;
    case VARIANT.DOTTED:
      return VARIANT.DOTTED_DOM;
    default:
      return VARIANT.STANDARD_DOM;
  }
};

export const getMinRadius = (
  sourceNode: NodeType,
  targetNode: NodeType,
): number => {
  const {
    width: sourceWidth, height: sourceHeight, isCollapsed: isSourceCollapsed,
  } = sourceNode;
  const {
    width: targetWidth = 0, height: targetHeight = 0, isCollapsed: isTargetCollapsed,
  } = targetNode;
  const nodesSizes = [sourceWidth, sourceHeight, targetWidth, targetHeight];
  const isCollapsed = isSourceCollapsed || isTargetCollapsed;

  if (isCollapsed) {
    nodesSizes.push(COLLAPSED_HEIGHT);
  }

  const minRadius = Math.min(...nodesSizes) / 2;

  return minRadius;
};

export const getEdgeHandleOffsetTranslation = (): string => {
  const offset = -EDGE_HANDLE_SIZE / 2;

  return `translate(${offset}, ${offset})`;
};

export const getOffset = (
  sourceNodeXY: ITargetPositionType,
  targetNodeXY: ITargetPositionType,
  offset: number,
): Array<number> => {
  const { x: sourceX, y: sourceY } = sourceNodeXY;
  const { x: targetX, y: targetY } = targetNodeXY;

  const lineLength = Math.sqrt(((targetX - sourceX) ** 2) + ((targetY - sourceY) ** 2));
  const k = offset / lineLength;

  return [
    (targetX - sourceX) * k,
    (targetY - sourceY) * k,
  ];
};

/**
 *
 *
 * @param {*} pt1
 * @param {*} pt2
 * @returns
 * @memberof Edge
 *
 * Calculates angle between 2 dots.
 */
export const calculateAngle = (
  src: ITargetPositionType,
  trg: ITargetPositionType,
): number => {
  const xComp = (trg.x || 0) - (src.x || 0);
  const yComp = (trg.y || 0) - (src.y || 0);

  const theta = Math.atan2(yComp, xComp);
  return theta * 180 / Math.PI;
};

/**
 *
 *
 * @param {*} srcTrgDataArray
 * @returns
 * @memberof Edge
 *
 * Provides API for curved lines using .curve() Example:
 * https://bl.ocks.org/d3indepth/64be9fc39a92ef074034e9a8fb29dcce
 */
export const lineFunction = (srcTrgDataArray: any) => d3.line()(srcTrgDataArray);

/**
 *
 *
 * @param {EdgeType} edge
 * @param {(HTMLDivElement | HTMLDocument)} [viewWrapperElem=document]
 * @returns
 * @memberof Edge
 *
 * Returns the edge element from the viewWrapper.
 */
export const getEdgePathElement = (
  edge: EdgeType,
  viewWrapperElem: HTMLDivElement,
): HTMLElement => viewWrapperElem.querySelector(
  `#edge-${edge.source}-${edge.target}-container>.edge-container>.edge>.edge-path`,
);

/**
 *
 *
 * @param {(Element | null)} edgePathElement
 * @returns
 * @memberof Edge
 *
 * If edgePathElement != null
 * converts an SVG path d property to an object with source and target objects
 * else
 * returns an object with source and target at position 0
 */
export const parsePathToXY = (edgePathElement: Element | null) => {
  const response = {
    source: { x: 0, y: 0 },
    target: { x: 0, y: 0 },
  };

  if (edgePathElement) {
    let d = edgePathElement.getAttribute('d');
    d = d && d.replace(/^M/, '');
    d = d && d.replace(/L/, ',');

    const dArr = (d && d.split(',')) || [];
    const [sourceX, sourceY, targetX, targetY] = dArr.map(dimension => parseFloat(dimension));

    if (dArr.length === 4) {
      response.source.x = sourceX;
      response.source.y = sourceY;
      response.target.x = targetX;
      response.target.y = targetY;
    }
  }

  return response;
};

/**
 *
 *
 * @returns
 * @memberof Edge
 *
 * Returns a default intersect object.
 */
export const getDefaultIntersectResponse = (): IntersectResponseType => ({
  xOff: 0,
  yOff: 0,
  intersect: {
    type: 'none',
    point: {
      x: 0,
      y: 0,
    },
  },
});

export const changeIntersect = (
  response: IntersectResponseType,
  points: ITargetPositionType,
  trgX: number,
  trgY: number,
): IntersectResponseType => {
  const [intersectPoint] = points;
  const xIntersect = intersectPoint.x;
  const yIntersect = intersectPoint.y;

  response.xOff = trgX - xIntersect;
  response.yOff = trgY - yIntersect;
  response.intersect = intersectPoint;
  return response;
};

/**
 *
 *
 * @param {*} defSvgRotatedRectElement
 * @param {*} src
 * @param {*} trg
 * @param {(HTMLDivElement | HTMLDocument)} [viewWrapperElem=document]
 * @returns
 * @memberof Edge
 */
export const getRotatedRectIntersect = (
  defSvgRotatedRectElement: HTMLElement,
  src: ITargetPositionType,
  trg: ITargetPositionType,
): IntersectResponseType => {
  const response = getDefaultIntersectResponse();
  const clientRect = defSvgRotatedRectElement.getBoundingClientRect();

  const widthAttr = defSvgRotatedRectElement.getAttribute('width');
  const heightAttr = defSvgRotatedRectElement.getAttribute('height');
  const w = widthAttr ? parseFloat(widthAttr) : clientRect.width;
  const h = heightAttr ? parseFloat(heightAttr) : clientRect.height;
  const trgX = trg.x || 0;
  const trgY = trg.y || 0;
  const srcX = src.x || 0;
  const srcY = src.y || 0;

  const top = trgY - h / 2;
  const bottom = trgY + h / 2;
  const left = trgX - w / 2;
  const right = trgX + w / 2;

  const line = shape('line', {
    x1: srcX, y1: srcY, x2: trgX, y2: trgY,
  });

  // define rectangle
  const rect = {
    topLeft: new Point2D(left, top),
    bottomRight: new Point2D(right, bottom),
  };

  // convert rectangle corners to polygon (list of points)
  const poly = [
    rect.topLeft,
    new Point2D(rect.bottomRight.x, rect.topLeft.y),
    rect.bottomRight,
    new Point2D(rect.topLeft.x, rect.bottomRight.y),
  ];

  // find center point of rectangle
  const center = rect.topLeft.lerp(rect.bottomRight, 0.5);

  // get the rotation
  const transform = defSvgRotatedRectElement.getAttribute('transform');
  let rotate = transform ? transform.replace(/(rotate.[0-9]*.)|[^]/g, '$1') : null;
  let angle = 0;
  if (rotate) {
    // get the number
    rotate = rotate.replace(/^rotate\(|\)$/g, '');
    // define rotation in radians
    angle = parseFloat(rotate) * Math.PI / 180.0;
  }
  // create matrix for rotating around center of rectangle
  const rotation = Matrix2D.rotationAt(angle, center);
  // create new rotated polygon
  const rotatedPoly = poly.map(p => p.transform(rotation));

  // find intersections
  const pathIntersect = Intersection.intersectLinePolygon(
    line.params[0],
    line.params[1],
    rotatedPoly,
  );

  if (pathIntersect.points.length > 0) {
    changeIntersect(response, pathIntersect.points, trgX, trgY);
  }

  return response;
};

/**
 *
 *
 * @param {*} defSvgPathElement
 * @param {*} src
 * @param {*} trg
 * @param {(HTMLDivElement | HTMLDocument)} [viewWrapperElem=document]
 * @returns
 * @memberof Edge
 *
 * Finds the path intersect.
 */
export const getPathIntersect = (
  defSvgPathElement: HTMLElement,
  src: ITargetPositionType,
  trg: ITargetPositionType,
): IntersectResponseType => {
  const response = getDefaultIntersectResponse();
  const { width: w, height: h } = defSvgPathElement.getBoundingClientRect();

  const trgX = trg.x || 0;
  const trgY = trg.y || 0;
  const srcX = src.x || 0;
  const srcY = src.y || 0;

  // calculate the positions of each corner relative to the trg position
  const top = trgY - h / 2;
  // const bottom = trgY + h / 2;
  const left = trgX - w / 2;
  // const right = trgX + w / 2;

  // modify the d property to add top and left to the x and y positions
  let d = defSvgPathElement.getAttribute('d');
  d = d.replace(/^M /, '');

  let dArr = d.split(' ');
  dArr = dArr.map((val, index) => {
    let isEnd = false;
    if (/Z$/.test(val)) {
      val = val.replace(/Z$/, '');
      isEnd = true;
    }
    // items % 2 are x positions
    if (index % 2 === 0) {
      return (parseFloat(val) + left) + (isEnd ? 'Z' : '');
    }
    return (parseFloat(val) + top) + (isEnd ? 'Z' : '');
  });

  const pathIntersect = intersect(
    shape('path', {
      d: `M ${dArr.join(' ')}`,
    }),
    shape('line', {
      x1: srcX,
      y1: srcY,
      x2: trgX,
      y2: trgY,
    }),
  );

  if (pathIntersect.points.length > 0) {
    changeIntersect(response, pathIntersect.points, trgX, trgY);
  }

  return response;
};

/**
 *
 *
 * @param {*} defSvgCircleElement
 * @param {*} src
 * @param {*} trg
 * @param {boolean} [includesArrow=true]
 * @param {(HTMLDivElement | HTMLDocument)} [viewWrapperElem=document]
 * @returns
 * @memberof Edge
 *
 * Finds the circle intersect.
 */
export const getCircleIntersect = (
  defSvgCircleElement: HTMLElement,
  src: ITargetPositionType,
  trg: ITargetPositionType,
): IntersectResponseType => {
  const response = getDefaultIntersectResponse();
  const { width, height } = defSvgCircleElement.getBoundingClientRect();

  let parentWidth = defSvgCircleElement.parentElement.getAttribute('width');
  if (parentWidth) {
    parentWidth = parseFloat(parentWidth);
  }
  let parentHeight = defSvgCircleElement.parentElement.getAttribute('height');
  if (parentHeight) {
    parentHeight = parseFloat(parentHeight);
  }

  const w = parentWidth || width;
  const h = parentHeight || height;
  const trgX = trg.x || 0;
  const trgY = trg.y || 0;
  const srcX = src.x || 0;
  const srcY = src.y || 0;
  // from the center of the node to the perimeter
  const offX = w / 2;
  const offY = h / 2;

  // Note: even though this is a circle function, we can use ellipse
  // because all circles are ellipses but not all ellipses are circles.
  const pathIntersect = intersect(
    shape('ellipse', {
      rx: offX,
      ry: offY,
      cx: trgX,
      cy: trgY,
    }),
    shape('line', {
      x1: srcX,
      y1: srcY,
      x2: trgX,
      y2: trgY,
    }),
  );

  if (pathIntersect.points.length > 0) {
    changeIntersect(response, pathIntersect.points, trgX, trgY);
  }

  return response;
};

/**
 *
 *
 * @param {*} src
 * @param {*} trg
 * @param {boolean} [includesArrow=true]
 * @param {(HTMLDivElement | HTMLDocument)} [viewWrapperElem=document]
 * @returns
 * @memberof Edge
 *
 * Returns rect intersects depending on type of svg item.
 */
export const calculateOffset = (
  src: ITargetPositionType,
  trg: ITargetPositionType,
  viewWrapperElem: HTMLDivElement,
) => {
  const response = getDefaultIntersectResponse();

  const nodeElem = document.getElementById(`node-${trg.id}`);
  if (!nodeElem) {
    return response;
  }

  const trgNode = nodeElem.querySelector('use.node');
  if (!trgNode || (trgNode && !trgNode.getAttributeNS)) {
    return response;
  }

  const xlinkHref = trgNode.getAttributeNS('http://www.w3.org/1999/xlink', 'href');
  if (!xlinkHref) {
    return response;
  }

  const defSvgRectElement = viewWrapperElem.querySelector(`defs>${xlinkHref} rect`);
  // Conditionally trying to select the element in other ways is faster than trying to
  // do the selection.
  const defSvgPathElement = !defSvgRectElement
    ? viewWrapperElem.querySelector(`defs>${xlinkHref} path`)
    : null;

  const defSvgCircleElement = (!defSvgRectElement && !defSvgPathElement)
    ? viewWrapperElem.querySelector(`defs>${xlinkHref} circle, defs>${xlinkHref} ellipse, defs>${xlinkHref} polygon`)
    : null;

  if (defSvgRectElement) {
    // it's a rectangle
    return {
      ...response,
      ...getRotatedRectIntersect(
        defSvgRectElement,
        src,
        trg,
      ),
    };
  }

  if (defSvgPathElement) {
    // it's a complex path
    return {
      ...response,
      ...getPathIntersect(
        defSvgPathElement,
        src,
        trg,
      ),
    };
  }

  // it's a circle or some other type
  return {
    ...response,
    ...getCircleIntersect(
      defSvgCircleElement,
      src,
      trg,
    ),
  };
};

/**
 *
 *
 * @param {*} edgeTypes
 * @param {*} data
 * @returns
 * @memberof Edge
 *
 * Returns a shapeId from the edge type.
 */
export const getXlinkHref = (edgeTypes: any, data: EdgeType): string | null => {
  if (data.type && edgeTypes[data.type]) {
    return edgeTypes[data.type].shapeId;
  }

  if (edgeTypes.standardEdge) {
    return edgeTypes.standardEdge.shapeId;
  }

  return null;
};
