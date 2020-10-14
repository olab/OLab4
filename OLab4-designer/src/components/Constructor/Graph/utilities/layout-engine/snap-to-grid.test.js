// @flow
import SnapToGrid from './snap-to-grid';

describe('SnapToGrid', () => {
  describe('class', () => {
    it('is defined', () => {
      expect(SnapToGrid).toBeDefined();
    });
  });

  describe('calculatePosition method', () => {
    it('adjusts the node position to be centered on a grid space', () => {
      const snapToGrid = new SnapToGrid({
        edges: [],
        edgeTypes: {},
        nodes: [],
        selected: null,
        onCreateNode: jest.fn(),
        onCreateEdge: jest.fn(),
        onDeleteNode: jest.fn(),
        onDeleteEdge: jest.fn(),
        onSelectNode: jest.fn(),
        onSelectEdge: jest.fn(),
        onSwapEdge: jest.fn(),
        onUpdateNode: jest.fn(),
        zoomControlsRef: {
          current: document.createElement('div'),
        },
        ACTION_SAVE_MAP_TO_UNDO: jest.fn(),
        gridSpacing: 10,
      });
      const newPosition = snapToGrid.calculatePosition({ x: 9, y: 8 });
      const expected = { x: 5, y: 5 };
      expect(JSON.stringify(newPosition)).toEqual(JSON.stringify(expected));
    });

    it('uses the default grid spacing', () => {
      const snapToGridWithoutSpacing = new SnapToGrid({
        edges: [],
        edgeTypes: {},
        nodes: [],
        selected: null,
        onCreateNode: jest.fn(),
        onCreateEdge: jest.fn(),
        onDeleteNode: jest.fn(),
        onDeleteEdge: jest.fn(),
        onSelectNode: jest.fn(),
        onSelectEdge: jest.fn(),
        onSwapEdge: jest.fn(),
        onUpdateNode: jest.fn(),
        zoomControlsRef: {
          current: document.createElement('div'),
        },
        ACTION_SAVE_MAP_TO_UNDO: jest.fn(),
      });
      const newPosition = snapToGridWithoutSpacing.calculatePosition({ x: 9, y: 8 });
      const expected = { x: 5, y: 5 };
      expect(JSON.stringify(newPosition)).toEqual(JSON.stringify(expected));
    });

    it('defaults the x and y to 0 when they are not present', () => {
      const snapToGrid = new SnapToGrid({
        edges: [],
        edgeTypes: {},
        nodes: [],
        selected: null,
        onCreateNode: jest.fn(),
        onCreateEdge: jest.fn(),
        onDeleteNode: jest.fn(),
        onDeleteEdge: jest.fn(),
        onSelectNode: jest.fn(),
        onSelectEdge: jest.fn(),
        onSwapEdge: jest.fn(),
        onUpdateNode: jest.fn(),
        zoomControlsRef: {
          current: document.createElement('div'),
        },
        ACTION_SAVE_MAP_TO_UNDO: jest.fn(),
        gridSpacing: 10,
      });
      const newPosition = snapToGrid.calculatePosition({ x: 0, y: 0 });
      const expected = { x: 0, y: 0 };
      expect(JSON.stringify(newPosition)).toEqual(JSON.stringify(expected));
    });

    it('moves the positions in the reverse direction', () => {
      const snapToGrid = new SnapToGrid({
        edges: [],
        edgeTypes: {},
        nodes: [],
        selected: null,
        onCreateNode: jest.fn(),
        onCreateEdge: jest.fn(),
        onDeleteNode: jest.fn(),
        onDeleteEdge: jest.fn(),
        onSelectNode: jest.fn(),
        onSelectEdge: jest.fn(),
        onSwapEdge: jest.fn(),
        onUpdateNode: jest.fn(),
        zoomControlsRef: {
          current: document.createElement('div'),
        },
        ACTION_SAVE_MAP_TO_UNDO: jest.fn(),
        gridSpacing: 10,
      });
      const newPosition = snapToGrid.calculatePosition({ x: 11, y: 11 });
      const expected = { x: 15, y: 15 };
      expect(JSON.stringify(newPosition)).toEqual(JSON.stringify(expected));
    });
  });
});
