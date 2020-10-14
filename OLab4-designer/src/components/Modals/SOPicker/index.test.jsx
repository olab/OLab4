// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { SOPicker } from '.';

describe('<SOPicker />', () => {
  let output = {};
  let x;
  let y;
  let isDragging;
  let connectDragSource;
  let ACTION_CLOSE_MODAL;
  let ACTION_SET_POSITION_MODAL;

  beforeEach(() => {
    x = 0;
    y = 0;
    isDragging = true;
    ACTION_CLOSE_MODAL = jest.fn();
    ACTION_SET_POSITION_MODAL = jest.fn();
    connectDragSource = jest.fn().mockReturnValue(<div />);
    output = shallow(
      <SOPicker
        x={x}
        y={y}
        isDragging={isDragging}
        ACTION_CLOSE_MODAL={ACTION_CLOSE_MODAL}
        ACTION_SET_POSITION_MODAL={ACTION_SET_POSITION_MODAL}
        connectDragSource={connectDragSource}
      />,
    );
  });

  describe('component is rendered', () => {
    it('should not render component cause it is dragging', () => {
      expect(output.getElement()).toBeNull();
    });

    it('should render component', () => {
      output.setProps({
        isDragging: false,
      });
      expect(output.getElement()).not.toBeNull();
    });
  });

  describe('handleCloseModal method', () => {
    output.instance().handleCloseModal();
    expect(ACTION_CLOSE_MODAL).toBeCalled();
  });

  describe('handleModalMove method', () => {
    it('should fire ACTION_SET_POSITION_MODAL reducer action', () => {
      output.instance().handleModalMove(5, 5);
      expect(ACTION_SET_POSITION_MODAL).toBeCalled();
    });
  });
});
