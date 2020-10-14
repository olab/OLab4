// @flow
import * as d3 from 'd3';
import React from 'react';

import { shallow } from 'enzyme';

import { Node } from '.';

describe('Node component', () => {
  let output = {};
  let nodeData;
  let onNodeMouseEnter;
  let onNodeMouseLeave;
  let onNodeMove;
  let onNodeSelected;
  let onNodeUpdate;
  let ACTION_SAVE_MAP_TO_UNDO;
  beforeEach(() => {
    nodeData = {
      id: '1',
      title: 'Test',
      type: 'emptyNode',
      x: 5,
      y: 10,
    };

    onNodeMouseEnter = jest.fn();
    onNodeMouseLeave = jest.fn();
    onNodeMove = jest.fn();
    onNodeSelected = jest.fn();
    onNodeUpdate = jest.fn();
    ACTION_SAVE_MAP_TO_UNDO = jest.fn();

    jest.spyOn(document, 'querySelector').mockReturnValue({
      getAttribute: jest.fn().mockReturnValue(100),
      getBoundingClientRect: jest.fn().mockReturnValue({
        width: 0,
        height: 0,
      }),
    });

    // this gets around d3 being readonly, we need to customize the event object
    let globalEvent = {

      sourceEvent: {},
    };
    // $flow-disable-line
    Object.defineProperty(d3, 'event', {
      get: () => globalEvent,
      set: (event) => {
        globalEvent = event;
      },
    });
    /* flow-enable */
    output = shallow(
      <Node
        id="test-node"
        data={nodeData}
        isSelected={false}
        onNodeMouseEnter={onNodeMouseEnter}
        onNodeMouseLeave={onNodeMouseLeave}
        onNodeMove={onNodeMove}
        onNodeSelected={onNodeSelected}
        onNodeUpdate={onNodeUpdate}
        viewWrapperElem={document.createElement('div')}
        ACTION_SAVE_MAP_TO_UNDO={ACTION_SAVE_MAP_TO_UNDO}
      />,
    );
  });

  describe('render method', () => {
    it('renders', () => {
      expect(output.getElement()).not.toBeNull();
      expect(output.props().transform).toEqual('translate(5, 10)');

      const nodeText = output.find(Node);
      expect(nodeText).toBeDefined();
    });

    it('calls handleMouseOver', () => {
      const event = {
        test: true,
      };
      output.find('g').props().onMouseOver(event);
      expect(onNodeMouseEnter).toHaveBeenCalledWith(event, nodeData, true);
    });

    it('calls handleMouseOut', () => {
      const event = {
        test: true,
      };
      output.setState({
        hovered: true,
      });
      output.find('g').props().onMouseOut(event);
      expect(onNodeMouseLeave).toHaveBeenCalledWith(event, nodeData);
      expect(output.state().hovered).toEqual(false);
    });
  });

  describe('handleMouseOut method', () => {
    it('sets hovered to false and calls the onNodeMouseLeave callback', () => {
      const event = {
        test: true,
      };
      output.setState({
        hovered: true,
      });
      output.instance().handleMouseOut(event);
      expect(output.state().hovered).toEqual(false);
      expect(onNodeMouseLeave).toHaveBeenCalledWith(event, nodeData);
    });
  });

  describe('handleMouseOver method', () => {
    it('calls the onNodeMouseEnter callback with the mouse down', () => {
      // need to set d3.event.buttons even though we're not testing it due to the mock
      // that we use above
      d3.event.buttons = 1;
      // this test cares about the passed-in event
      const event = {
        buttons: 1,
      };
      output.setState({
        hovered: false,
      });
      output.instance().handleMouseOver(event);
      expect(output.state().hovered).toEqual(false);
      expect(onNodeMouseEnter).toHaveBeenCalledWith(event, nodeData, false);
    });

    it('sets hovered to true when the mouse is not down', () => {
      const event = {
        buttons: 0,
      };
      output.setState({
        hovered: false,
      });
      output.instance().handleMouseOver(event);
      expect(output.state().hovered).toEqual(true);
      expect(onNodeMouseEnter).toHaveBeenCalledWith(event, nodeData, true);
    });

    it('sets hovered to true when the mouse is not down using d3 events', () => {
      d3.event = {
        buttons: 0,
      };
      output.setState({
        hovered: false,
      });
      const event = null;
      output.instance().handleMouseOver(event);
      expect(output.state().hovered).toEqual(true);
      expect(onNodeMouseEnter).toHaveBeenCalledWith(event, nodeData, true);
    });
  });

  describe('handleDragEnd method', () => {
    it('updates and selects the node using the callbacks', () => {
      output.instance().nodeRef = {
        current: {
          parentElement: null,
        },
      };
      // mock the event property
      d3.event = {
        sourceEvent: {
          shiftKey: true,
        },
      };
      output.instance().handleDragEnd();
      expect(onNodeUpdate).toHaveBeenCalledWith(
        { x: 5, y: 10 },
        '1',
        true,
      );
      expect(onNodeSelected).toHaveBeenCalledWith(nodeData, '1', true);
    });
  });

  describe('handleDragStart method', () => {
    let grandparent;
    let parentElement;
    beforeEach(() => {
      grandparent = {
        appendChild: jest.fn(),
      };
      parentElement = {
        nextSibling: 'blah',
        parentElement: grandparent,
      };
      output.instance().nodeRef.current = {
        parentElement,
      };
    });

    it('assigns an oldSibling so that the element can be put back', () => {
      output.instance().nodeRef.current = {
        parentElement,
      };

      output.instance().handleDragStart();

      expect(grandparent).toEqual(grandparent);
    });

    it('moves the element in the DOM', () => {
      output.instance().oldSibling = {};
      output.instance().handleDragStart();
      expect(grandparent).toEqual(grandparent);
    });
  });

  describe('handleMouseMove method', () => {
    it('calls the onNodeMove callback', () => {
      output.instance().handleMouseMove();
      expect(onNodeMove).not.toHaveBeenCalled();
    });

    it('calls the onNodeMove callback with the shiftKey pressed', () => {
      d3.event = {
        sourceEvent: {
          buttons: 1,
          shiftKey: true,
        },
        x: 20,
        y: 50,
      };
      output.instance().handleMouseMove();
      expect(onNodeMove).toHaveBeenCalledWith(
        { x: 20, y: 50 },
        '1',
        true,
      );
    });

    it('calls the onNodeMove callback with the shiftKey not pressed', () => {
      d3.event = {
        sourceEvent: {
          buttons: 1,
          shiftKey: false,
        },
        x: 20,
        y: 50,
      };
      output.instance().handleMouseMove();
      expect(onNodeMove).toHaveBeenCalledWith(
        { x: 20, y: 50 },
        '1',
        false,
      );
    });

    it('uses a layoutEngine to obtain a new position', () => {
      const layoutEngine = {
        calculatePosition: jest.fn().mockReturnValue({
          x: 100,
          y: 200,
        }),
        getPositionForNode: jest.fn().mockReturnValue(() => ({
          x: 100,
          y: 200,
        })),
      };

      output.setProps({
        layoutEngine,
      });

      d3.event = {
        sourceEvent: {
          buttons: 1,
          shiftKey: false,
        },
        x: 20,
        y: 50,
      };
      output.instance().handleMouseMove();

      expect(onNodeMove).toHaveBeenCalledWith(
        { x: 100, y: 200 },
        '1',
        false,
      );
    });
  });
});
