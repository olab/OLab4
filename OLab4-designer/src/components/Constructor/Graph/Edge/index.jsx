// @flow
/*
This component renders standalone edge.
*/
import React from 'react';

import {
  getVariantValueDOM, getMinRadius,
  getEdgeHandleOffsetTranslation, getOffset,
  calculateAngle, lineFunction, getXlinkHref,
} from './utils';
import { EDGE_HANDLE_SIZE } from './config';

import type { IEdgeProps } from './types';

import { EdgeWrapper } from './styles';

export class Edge extends React.Component<IEdgeProps> {
  constructor(props: IEdgeProps) {
    super(props);
    this.edgeOverlayRef = React.createRef();
  }

  getEdgeHandleTranslation = () => {
    const pathDescription = this.getPathDescription();
    const pathDescriptionArr = pathDescription
      .replace(/^M/, '')
      .replace(/L/, ',')
      .split(',');

    const diffX = parseFloat(pathDescriptionArr[2]) - parseFloat(pathDescriptionArr[0]);
    const diffY = parseFloat(pathDescriptionArr[3]) - parseFloat(pathDescriptionArr[1]);
    const x = parseFloat(pathDescriptionArr[0]) + diffX / 2;
    const y = parseFloat(pathDescriptionArr[1]) + diffY / 2;

    return `translate(${x}, ${y})`;
  }

  getEdgeHandleRotation = () => {
    const { sourceNode: src, targetNode: trg } = this.props;
    const theta = calculateAngle(src, trg);

    return `rotate(${theta})`;
  }

  getEdgeHandleTransformation = () => {
    const translation = this.getEdgeHandleTranslation();
    const rotation = this.getEdgeHandleRotation();
    const offset = getEdgeHandleOffsetTranslation();

    return `${translation} ${rotation} ${offset}`;
  }

  getPathDescription() {
    const { sourceNode = {}, targetNode = {}, hasSibling } = this.props;

    const trgX = (targetNode && targetNode.x) ? targetNode.x : 0;
    const trgY = (targetNode && targetNode.y) ? targetNode.y : 0;
    const srcX = (sourceNode && sourceNode.x) ? sourceNode.x : 0;
    const srcY = (sourceNode && sourceNode.y) ? sourceNode.y : 0;

    const minRadius = getMinRadius(sourceNode, targetNode);

    const thetaDegrees = calculateAngle(sourceNode, targetNode);
    const thetaRadians = (90 - thetaDegrees) * (Math.PI / 180);

    let linePoints;

    if (hasSibling) {
      const deltaX = -minRadius * Math.cos(thetaRadians);
      const deltaY = minRadius * Math.sin(thetaRadians);

      linePoints = [
        [
          srcX - deltaX,
          srcY - deltaY,
        ],
        [
          trgX - deltaX,
          trgY - deltaY,
        ],
      ];
    } else {
      const [srcDeltaX, srcDeltaY] = getOffset(
        { x: srcX, y: srcY },
        { x: trgX, y: trgY },
        minRadius,
      );

      const [trgDeltaX, trgDeltaY] = getOffset(
        { x: trgX, y: trgY },
        { x: srcX, y: srcY },
        minRadius,
      );

      linePoints = [
        [
          srcX + srcDeltaX,
          srcY + srcDeltaY,
        ],
        [
          trgX + trgDeltaX,
          trgY + trgDeltaY,
        ],
      ];
    }

    return lineFunction(linePoints);
  }

  edgeOverlayRef: { current: null | Element };

  render() {
    const {
      data, edgeTypes, viewWrapperElem, isSelected: selected, isLinkingStarted, edgeDefaults,
    } = this.props;

    if (!viewWrapperElem) {
      return null;
    }

    const id = `${data.source || ''}_${data.target}`;

    return (
      <g className="edge-container" data-source={data.source} data-target={data.target}>
        <EdgeWrapper
          selected={selected}
          isLinkingStarted={isLinkingStarted}
        >
          <path
            stroke={data.color || edgeDefaults.color}
            strokeWidth={`${data.thickness || edgeDefaults.thickness}px`}
            strokeDasharray={getVariantValueDOM(data.variant)}
            d={this.getPathDescription() || undefined}
          />
          <use
            id={id}
            className="edge-use"
            ref={this.edgeOverlayRef}
            width={EDGE_HANDLE_SIZE}
            height={EDGE_HANDLE_SIZE}
            xlinkHref={getXlinkHref(edgeTypes, data)}
            transform={this.getEdgeHandleTransformation()}
            data-source={data.source}
            data-target={data.target}
            fill={data.color || edgeDefaults.color}
          />
        </EdgeWrapper>
      </g>
    );
  }
}

export default Edge;
