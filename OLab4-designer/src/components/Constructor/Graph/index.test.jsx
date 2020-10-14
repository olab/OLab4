// @flow
import React from 'react';
import cloneDeep from 'lodash.clonedeep';
import { shallow } from 'enzyme';

import { Graph } from '.';
import sample from '../../../helpers/nodes_sample';

describe('<Graph />', () => {
  let output = {};
  let isFullScreen;
  let minZoom;
  let maxZoom;
  let graph;
  let isUndoAvailable;
  let isRedoAvailable;
  let layoutEngineType;
  let ACTION_UNDO_MAP;
  let ACTION_REDO_MAP;
  let ACTION_SELECT_ITEM;
  let ACTION_CREATE_NODE;
  let ACTION_CREATE_EDGE;
  let ACTION_UPDATE_NODE;
  let ACTION_DELETE_NODE;
  let ACTION_DELETE_EDGE;
  let ACTION_SWAP_EDGE;
  let connectDropTarget;
  beforeEach(() => {
    isFullScreen = false;
    isUndoAvailable = false;
    isRedoAvailable = false;
    minZoom = 15;
    maxZoom = 150;
    layoutEngineType = 'None';
    graph = cloneDeep(sample);
    ACTION_UNDO_MAP = jest.fn();
    ACTION_REDO_MAP = jest.fn();
    ACTION_SELECT_ITEM = jest.fn();
    ACTION_CREATE_NODE = jest.fn();
    ACTION_CREATE_EDGE = jest.fn();
    ACTION_UPDATE_NODE = jest.fn();
    ACTION_DELETE_NODE = jest.fn();
    ACTION_DELETE_EDGE = jest.fn();
    ACTION_SWAP_EDGE = jest.fn();
    connectDropTarget = jest.fn().mockReturnValue(<div id="graph" />);
    output = shallow(
      <Graph
        isFullScreen={isFullScreen}
        minZoom={minZoom}
        maxZoom={maxZoom}
        graph={graph}
        isUndoAvailable={isUndoAvailable}
        isRedoAvailable={isRedoAvailable}
        layoutEngineType={layoutEngineType}
        ACTION_UNDO_MAP={ACTION_UNDO_MAP}
        ACTION_REDO_MAP={ACTION_REDO_MAP}
        ACTION_SELECT_ITEM={ACTION_SELECT_ITEM}
        ACTION_CREATE_NODE={ACTION_CREATE_NODE}
        ACTION_CREATE_EDGE={ACTION_CREATE_EDGE}
        ACTION_UPDATE_NODE={ACTION_UPDATE_NODE}
        ACTION_DELETE_NODE={ACTION_DELETE_NODE}
        ACTION_DELETE_EDGE={ACTION_DELETE_EDGE}
        ACTION_SWAP_EDGE={ACTION_SWAP_EDGE}
        connectDropTarget={connectDropTarget}
      />,
    );
  });

  describe('render method', () => {
    it('renders', () => {
      expect(output.getElement()).not.toBeNull();
      expect(output.props().id).toEqual('graph');
    });
  });

  describe('getSelectedItem getter method', () => {
    it('should return \'null\'', () => {
      expect(output.instance().getSelectedItem).toBeNull();
    });

    it('should return first edge', () => {
      const g = cloneDeep(sample);
      const firstEdge = g.edges[0].data;
      output.instance().onSelectItem(firstEdge);
      expect(ACTION_SELECT_ITEM).toHaveBeenCalled();

      g.edges[0].isSelected = true;
      output.setProps({
        graph: g,
      });
      expect(output.instance().getSelectedItem).toEqual(firstEdge);
    });
  });

  describe('getSelectedNode getter method', () => {
    it('should return \'null\'', () => {
      expect(output.instance().getSelectedNode).toBeNull();
    });

    it('should return first node', () => {
      const g = cloneDeep(sample);
      const firstNode = g.nodes[0].data;
      output.instance().onSelectItem(firstNode);
      expect(ACTION_SELECT_ITEM).toHaveBeenCalled();

      g.nodes[0].isSelected = true;
      output.setProps({
        graph: g,
      });
      expect(output.instance().getSelectedItem).toEqual(firstNode);
    });
  });

  describe('getSelectedEdge getter method', () => {
    it('should return \'null\'', () => {
      expect(output.instance().getSelectedEdge).toBeNull();
    });

    it('should return first edge', () => {
      const g = cloneDeep(sample);
      const firstEdge = g.edges[0].data;
      output.instance().onSelectItem(firstEdge);
      expect(ACTION_SELECT_ITEM).toHaveBeenCalled();

      g.edges[0].isSelected = true;
      output.setProps({
        graph: g,
      });
      expect(output.instance().getSelectedItem).toEqual(firstEdge);
    });
  });

  describe('onSelectItem method', () => {
    it('should fire ACTION_SELECT_ITEM redux action', () => {
      output.instance().onSelectItem(null);
      expect(ACTION_SELECT_ITEM).toHaveBeenCalled();
    });
  });

  describe('onCreateNode method', () => {
    it('should fire ACTION_CREATE_NODE redux action', () => {
      output.instance().onCreateNode(5, 5);
      expect(ACTION_CREATE_NODE).toHaveBeenCalled();
    });
  });

  describe('onCreateEdge method', () => {
    it('should fire ACTION_CREATE_EDGE redux action', () => {
      const [sourceNode, targetNode] = cloneDeep(sample.nodes);
      output.instance().onCreateEdge(sourceNode.data, targetNode.data);
      expect(ACTION_CREATE_EDGE).toHaveBeenCalled();
    });
  });

  describe('onUpdateNode method', () => {
    it('should fire ACTION_UPDATE_NODE redux action', () => {
      const node = cloneDeep(sample.nodes[0]);
      const dataNode = node.data;
      dataNode.x += 10;
      output.instance().onUpdateNode(dataNode);
      expect(ACTION_UPDATE_NODE).toHaveBeenCalled();
    });
  });

  describe('onDeleteNode method', () => {
    it('should fire ACTION_DELETE_NODE redux action', () => {
      const node = cloneDeep(sample.nodes[0]);
      const dataNode = node.data;
      output.instance().onDeleteNode(dataNode);
      expect(ACTION_DELETE_NODE).toHaveBeenCalled();
    });
  });

  describe('onDeleteEdge method', () => {
    it('should fire ACTION_DELETE_EDGE redux action', () => {
      const edge = cloneDeep(sample.edges[0]);
      const dataEdge = edge.data;
      output.instance().onDeleteEdge(dataEdge);
      expect(ACTION_DELETE_EDGE).toHaveBeenCalled();
    });
  });

  describe('onSwapEdge method', () => {
    it('should fire ACTION_SWAP_EDGE redux action', () => {
      const g = cloneDeep(sample);
      const [sourceNode, targetNode] = g.nodes;
      const [edge] = g.edges;
      output.instance().onSwapEdge(sourceNode, targetNode, edge);
      expect(ACTION_SWAP_EDGE).toHaveBeenCalled();
    });
  });

  describe('onUndo method', () => {
    it('should not fire ACTION_UNDO_MAP redux action', () => {
      output.instance().onUndo();
      expect(ACTION_UNDO_MAP).not.toHaveBeenCalled();
    });
    it('should not fire ACTION_UNDO_MAP redux action', () => {
      output.setProps({
        isUndoAvailable: true,
      });
      output.instance().onUndo();
      expect(ACTION_UNDO_MAP).toHaveBeenCalled();
    });
  });

  describe('onRedo method', () => {
    it('should not fire ACTION_REDO_MAP redux action', () => {
      output.instance().onRedo();
      expect(ACTION_REDO_MAP).not.toHaveBeenCalled();
    });
    it('should fire ACTION_REDO_MAP redux action', () => {
      output.setProps({
        isRedoAvailable: true,
      });
      output.instance().onRedo();
      expect(ACTION_REDO_MAP).toHaveBeenCalled();
    });
  });

  describe('onCopySelected method', () => {
    it('state should not be changed', () => {
      output.instance().onCopySelected();
      expect(output.state()).toBeNull();
    });
    it('state should be changed', () => {
      const g = cloneDeep(sample);
      g.nodes[0].isSelected = true;
      output.setProps({
        graph: g,
      });

      output.instance().onCopySelected();
      expect(output.state().copiedNode).toBeDefined();
    });
  });

  describe('onPasteSelected method', () => {
    it('should fire ACTION_CREATE_NODE redux action', () => {
      const g = cloneDeep(sample);
      g.nodes[0].isSelected = true;
      output.setProps({
        graph: g,
      });

      output.instance().onCopySelected();
      output.instance().onPasteSelected();
      expect(ACTION_CREATE_NODE).toBeCalled();
    });
  });
});
