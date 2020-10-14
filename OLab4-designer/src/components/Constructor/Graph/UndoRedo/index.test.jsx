// @flow

import React from 'react';

import { mount } from 'enzyme';
import { GraphUndoRedoButtons } from '.';

describe('<GraphUndoRedoButtons />', () => {
  const classes = {};
  let wrapper;
  let ACTION_UNDO_MAP;
  let ACTION_REDO_MAP;

  beforeEach(() => {
    ACTION_UNDO_MAP = jest.fn();
    ACTION_REDO_MAP = jest.fn();
    wrapper = mount(<GraphUndoRedoButtons
      classes={classes}
      ACTION_UNDO_MAP={ACTION_UNDO_MAP}
      ACTION_REDO_MAP={ACTION_REDO_MAP}
      isUndoAvailable
      isRedoAvailable
    />);
  });

  it('renders successfully', () => {
    expect(wrapper).toHaveLength(1);
  });

  it('have two child components', () => {
    expect(wrapper.find('svg')).toHaveLength(2);
  });

  it('call ACTION_UNDO_MAP on click', () => {
    wrapper.find('button').at(0).simulate('click');
    expect(ACTION_UNDO_MAP).toBeCalled();
  });

  it('call ACTION_REDO_MAP on click', () => {
    wrapper.find('button').at(1).simulate('click');
    expect(ACTION_REDO_MAP).toBeCalled();
  });

  it('not call ACTION_UNDO_MAP and ACTION_REDO_MAP when Undo and Redo buttons are disabled', () => {
    wrapper.setProps({
      isUndoAvailable: false,
      isRedoAvailable: false,
    });
    wrapper.find('button').at(0).simulate('click');
    wrapper.find('button').at(1).simulate('click');
    expect(ACTION_UNDO_MAP).not.toBeCalled();
    expect(ACTION_REDO_MAP).not.toBeCalled();
  });
});
