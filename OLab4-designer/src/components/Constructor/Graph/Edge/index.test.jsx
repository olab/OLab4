// @flow
import React from 'react';
import { shallow } from 'enzyme';
import { Point2D } from 'kld-intersections';

import { Edge } from '.';

const StandardEdgeShape = (
  <symbol width="24" height="24" viewBox="0 0 24 24">
    <circle r="12" transform="matrix(1 0 0 -1 12 12)" fill="#D3DAE1" />

    <g transform="translate(18, 11) rotate(90)">
      <path d="M1 13L1 1" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </g>
  </symbol>
);

describe('<Edge />', () => {
  let output = {};
  let data;
  let edgeTypes;
  let sourceNode;
  let targetNode;
  let isSelected;

  beforeEach(() => {
    data = {
      handleText: 'test',
      source: 'foo',
      target: 'bar',
      type: 'fake',
    };
    edgeTypes = {
      standardEdge: {
        shape: StandardEdgeShape,
        shapeId: 'empty',
      },
      fake: {
        shape: StandardEdgeShape,
        shapeId: 'blah',
      },
    };
    sourceNode = {
      x: 10,
      y: 20,
    };
    targetNode = {
      x: 100,
      y: 200,
    };
    isSelected = false;
    output = shallow(
      <Edge
        data={data}
        edgeTypes={edgeTypes}
        sourceNode={sourceNode}
        targetNode={targetNode}
        isSelected={isSelected}
        viewWrapperElem={document.createElement('div')}
      />,
    );
  });

  describe('render method', () => {
    it('component is rendered', () => {
      expect(output.getElement()).not.toBeNull();
      expect(output.props()['data-source']).toEqual('foo');
      expect(output.props()['data-target']).toEqual('bar');
    });

    it('does not render handleText when there is none', () => {
      output.setProps({
        data: {
          ...data,
          handleText: '',
        },
      });
      const handleText = output.find('text');
      expect(handleText.length).toEqual(0);
    });
  });

  describe('calculateAngle static method', () => {
    it('calculates angel between two dots', () => {
      const angle = Edge.calculateAngle(sourceNode, targetNode);
      expect(angle.toFixed(1)).toEqual('63.4');
    });
  });

  describe('lineFunction static method', () => {
    it('returns a translation', () => {
      const lineStr = Edge.lineFunction([sourceNode, targetNode]);
      expect(lineStr).toEqual('M10,20L100,200');
    });
  });

  describe('getEdgePathElement static method', () => {
    it('returns the edge element from the viewWrapper', () => {
      const viewWrapperElem = {
        querySelector: jest.fn(),
      };
      const fakeEdge = {
        source: 'fake1',
        target: 'fake2',
      };
      Edge.getEdgePathElement(fakeEdge, viewWrapperElem);
      expect(viewWrapperElem.querySelector)
        .toHaveBeenCalledWith('#edge-fake1-fake2-container>.edge-container>.edge>.edge-path');
    });

    it('returns the edge element from the document', () => {
      jest.spyOn(document, 'querySelector');
      const fakeEdge = {
        source: 'fake1',
        target: 'fake2',
      };
      Edge.getEdgePathElement(fakeEdge);
      expect(document.querySelector)
        .toHaveBeenCalledWith('#edge-fake1-fake2-container>.edge-container>.edge>.edge-path');
      document.querySelector.mockRestore();
    });
  });

  describe('parsePathToXY static method', () => {
    it('converts an SVG path d property to an object with source and target objects', () => {
      const edgePathElement = {
        getAttribute: jest.fn().mockReturnValue('M33,43L224,282'),
      };
      const result = Edge.parsePathToXY(edgePathElement);
      const expected = {
        source: { x: 33, y: 43 },
        target: { x: 224, y: 282 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('returns an object with source and target at position 0', () => {
      const result = Edge.parsePathToXY();
      const expected = {
        source: { x: 0, y: 0 },
        target: { x: 0, y: 0 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('returns a default reponse when there is no d attribute', () => {
      const edgePathElement = {
        getAttribute: jest.fn().mockReturnValue(''),
      };
      const result = Edge.parsePathToXY(edgePathElement);
      const expected = {
        source: { x: 0, y: 0 },
        target: { x: 0, y: 0 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });
  });

  describe('getDefaultIntersectResponse static method', () => {
    it('returns a default intersect object', () => {
      const result = Edge.getDefaultIntersectResponse();
      const expected = {
        xOff: 0,
        yOff: 0,
        intersect: {
          type: 'none',
          point: {
            x: 0,
            y: 0,
          },
        },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });
  });

  describe('getRotatedRectIntersect', () => {
    let viewWrapperElem;
    let rectElement;
    let source;
    let target;
    beforeEach(() => {
      const rect = {
        bottom: 10,
        height: 20,
        left: 30,
        right: 40,
        top: 50,
        width: 60,
      };
      const boundingClientRectMock = jest.fn().mockImplementation(() => rect);
      viewWrapperElem = {
        querySelector: jest.fn().mockImplementation(() => ({
          getBoundingClientRect: boundingClientRectMock,
        })),
      };

      rectElement = document.createElement('div');
      rectElement.setAttribute('height', '10');
      rectElement.setAttribute('width', '10');
      jest.spyOn(rectElement, 'getBoundingClientRect').mockReturnValue({
        width: 15,
        height: 15,
      });
      source = new Point2D(5, 10);
      target = new Point2D(15, 20);
    });

    afterEach(() => {
      rectElement.getBoundingClientRect.mockRestore();
    });

    it('gets the intersect', () => {
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 5,
        yOff: 5,
        intersect: { x: 10, y: 15 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('does includes the arrow', () => {
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 5,
        yOff: 5,
        intersect: { x: 10, y: 15 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('uses the clientRect for width and height', () => {
      rectElement.removeAttribute('height');
      rectElement.removeAttribute('width');
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 7.5,
        yOff: 7.5,
        intersect: { x: 7.5, y: 12.5 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('uses 0 when trg and src do not have x and y', () => {
      source = new Point2D();
      target = new Point2D();
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 0,
        yOff: 0,
        intersect: {
          type: 'none',
          point: { x: 0, y: 0 },
        },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('handles rotates rectangles', () => {
      rectElement.setAttribute('transform', 'rotate(45)');
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 3.535533905932736,
        yOff: 3.5355339059327378,
        intersect: { x: 11.464466094067264, y: 16.464466094067262 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('points at the bottom', () => {
      source = new Point2D(5, 20);
      target = new Point2D(5, 5);
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 0,
        yOff: -5,
        intersect: { x: 5, y: 10 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('points at the left', () => {
      source = new Point2D(-5, 5);
      target = new Point2D(5, 5);
      const result = Edge.getRotatedRectIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 5,
        yOff: 0,
        intersect: { x: 0, y: 5 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });
  });

  describe('getPathIntersect static method', () => {
    let viewWrapperElem;
    let rectElement;
    let source;
    let target;
    beforeEach(() => {
      const rect = {
        bottom: 10,
        height: 20,
        left: 30,
        right: 40,
        top: 50,
        width: 60,
      };
      const boundingClientRectMock = jest.fn().mockImplementation(() => rect);
      viewWrapperElem = {
        querySelector: jest.fn().mockImplementation(() => ({
          getBoundingClientRect: boundingClientRectMock,
        })),
      };

      rectElement = document.createElement('div');
      rectElement.setAttribute('height', '10');
      rectElement.setAttribute('width', '10');
      jest.spyOn(rectElement, 'getBoundingClientRect').mockReturnValue({
        width: 15,
        height: 15,
      });
      source = new Point2D(5, 10);
      target = new Point2D(15, 20);
    });

    afterEach(() => {
      rectElement.getBoundingClientRect.mockRestore();
    });

    it('finds the intersect', () => {
      rectElement.setAttribute('d', 'M 0 0 15 0 15 15 0 15Z');
      const result = Edge.getPathIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 7.5,
        yOff: 7.5,
        intersect: { x: 7.5, y: 12.5 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });
  });

  describe('getCircleIntersect static method', () => {
    let viewWrapperElem;
    let rectElement;
    let source;
    let target;
    beforeEach(() => {
      const rect = {
        bottom: 10, height: 20, left: 30, right: 40, top: 50, width: 60,
      };
      const boundingClientRectMock = jest.fn().mockImplementation(() => rect);
      viewWrapperElem = {
        querySelector: jest.fn().mockImplementation(() => ({
          getBoundingClientRect: boundingClientRectMock,
        })),
      };

      const parentElement = document.createElement('div');
      parentElement.setAttribute('width', '10');
      parentElement.setAttribute('height', '10');

      rectElement = document.createElement('div');
      rectElement.setAttribute('height', '10');
      rectElement.setAttribute('width', '10');
      jest.spyOn(rectElement, 'getBoundingClientRect').mockReturnValue({
        width: 15,
        height: 15,
      });

      parentElement.appendChild(rectElement);

      source = new Point2D(5, 10);
      target = new Point2D(15, 20);
    });

    afterEach(() => {
      rectElement.getBoundingClientRect.mockRestore();
    });

    it('finds the intersect', () => {
      const result = Edge.getCircleIntersect(
        rectElement,
        source,
        target,
      );
      const expected = {
        xOff: 3.5355339059327378,
        yOff: 3.5355339059327378,
        intersect: { x: 11.464466094067262, y: 16.464466094067262 },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });
  });

  describe('calculateOffset static method', () => {
    let viewWrapperElem;
    let source;
    let target;
    let defaultExpected;
    let rectElement;
    beforeEach(() => {
      rectElement = document.createElement('div');
      rectElement.setAttribute('height', '10');
      rectElement.setAttribute('width', '10');
      jest.spyOn(rectElement, 'getBoundingClientRect').mockReturnValue({
        width: 15,
        height: 15,
      });
      viewWrapperElem = {
        querySelector: jest.fn().mockImplementation(() => rectElement),
      };

      source = new Point2D(5, 10);
      source.id = 'test';
      target = new Point2D(15, 20);
      target.id = 'test2';

      defaultExpected = {
        xOff: 0,
        yOff: 0,
        intersect: { type: 'none', point: { x: 0, y: 0 } },
      };
    });

    it('returns a default response when there is no matching nodeKey', () => {
      source.id = '';
      target.id = '';
      const result = Edge.calculateOffset(source, target, viewWrapperElem);
      const expected = defaultExpected;
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('returns a default response when there is no matching node element', () => {
      const result = Edge.calculateOffset(source, target, viewWrapperElem);
      const expected = {
        xOff: 0,
        yOff: 0,
        intersect: { type: 'none', point: { x: 0, y: 0 } },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));
    });

    it('returns a default response when there is no matching target node', () => {
      const node = {
        querySelector: jest.fn().mockImplementation(() => ({})),
      };

      jest.spyOn(document, 'getElementById').mockImplementation(() => node);

      const result = Edge.calculateOffset(source, target, viewWrapperElem);
      const expected = defaultExpected;
      expect(document.getElementById).toHaveBeenCalledWith('node-test2');
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));

      document.getElementById.mockRestore();
    });

    it('returns a default response when there is no xlinkHref', () => {
      const trgNode = {
        getAttributeNS: jest.fn().mockImplementation(() => null),
      };
      const node = {
        querySelector: jest.fn().mockImplementation(() => trgNode),
      };
      jest.spyOn(document, 'getElementById').mockImplementation(() => node);

      const result = Edge.calculateOffset(source, target, viewWrapperElem);
      const expected = defaultExpected;
      expect(document.getElementById).toHaveBeenCalledWith('node-test2');
      expect(trgNode.getAttributeNS).toHaveBeenCalledWith('http://www.w3.org/1999/xlink', 'href');
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));

      document.getElementById.mockRestore();
    });

    it('gets a response for a rect element', () => {
      const trgNode = {
        getAttributeNS: jest.fn().mockImplementation(() => 'test'),
      };
      const node = {
        querySelector: jest.fn().mockImplementation(() => trgNode),
      };
      jest.spyOn(document, 'getElementById').mockImplementation(() => node);

      const result = Edge.calculateOffset(source, target, viewWrapperElem);
      const expected = {
        xOff: 5,
        yOff: 5,
        intersect: {
          x: 10,
          y: 15,
        },
      };
      expect(JSON.stringify(result)).toEqual(JSON.stringify(expected));

      document.getElementById.mockRestore();
    });
  });

  describe('getXlinkHref static method', () => {
    it('returns a shapeId from the edge type', () => {
      const typeId = Edge.getXlinkHref(edgeTypes, data);
      expect(typeId).toEqual('blah');
    });

    it('returns a shapeId from the empty edge type', () => {
      data.type = 'nomatch';
      const typeId = Edge.getXlinkHref(edgeTypes, data);
      expect(typeId).toEqual('empty');
    });

    it('returns null when there is no empty or matchin edge type', () => {
      data.type = 'nomatch';
      delete edgeTypes.standardEdge;
      const typeId = Edge.getXlinkHref(edgeTypes, data);
      expect(typeId).toEqual(null);
    });
  });

  describe('getEdgeHandleTranslation method', () => {
    it('returns a translation', () => {
      const handleTranslation = output.instance().getEdgeHandleTranslation();
      expect(handleTranslation).toEqual('translate(55, 110)');
    });
  });

  describe('getEdgeHandleOffsetTranslation method', () => {
    it('returns an offset translation', () => {
      const handleOffset = output.instance().getEdgeHandleOffsetTranslation();
      expect(handleOffset).toEqual('translate(-25, -25)');
    });

    it('returns an offset translation when the handleSize is not set', () => {
      output.setProps({
        edgeHandleSize: null,
      });
      const handleOffset = output.instance().getEdgeHandleOffsetTranslation();
      expect(handleOffset).toEqual('translate(0, 0)');
    });
  });

  describe('getEdgeHandleRotation method', () => {
    it('returns a rotation', () => {
      const handleRotation = output.instance().getEdgeHandleRotation();
      expect(handleRotation).toEqual('rotate(63.43494882292201)');
    });
  });

  describe('getEdgeHandleTransformation method', () => {
    it('returns a translation, rotation, and offset', () => {
      const handleTransformation = output.instance().getEdgeHandleTransformation(data);
      expect(handleTransformation).toEqual('translate(55, 110) rotate(63.43494882292201) translate(-25, -25)');
    });
  });

  describe('getPathDescription method', () => {
    it('returns a path description', () => {
      const pathDescription = output.instance().getPathDescription(data);
      expect(pathDescription).toEqual('M10,20L100,200');
    });
  });

  describe('renderHandleText method', () => {
    it('returns a text element with the handleText inside', () => {
      const expectedData = {
        handleText: 'Fake',
      };
      const handleText = output.instance().renderHandleText(expectedData);
      expect(handleText.props.children).toEqual('Fake');
    });
  });
});
