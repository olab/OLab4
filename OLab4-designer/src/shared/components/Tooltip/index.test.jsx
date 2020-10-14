// @flow
import React from 'react';

import { mount } from 'enzyme';
import { CustomizedTooltip } from '.';

describe('<App />', () => {
  let output = {};
  let classes;
  let arrow;
  let isClickable;
  let tooltipText;
  beforeEach(() => {
    classes = {
      arrow: 'arrow',
      styleTooltip: 'styleTooltip',
      arrowPopper: 'arrowPopper',
    };
    arrow = true;
    isClickable = false;
    tooltipText = 'TooltipText';
    output = mount(
      <CustomizedTooltip
        classes={classes}
        tooltipText={tooltipText}
        arrow={arrow}
        isClickable={isClickable}
      >
        <div />
      </CustomizedTooltip>,
    );
  });

  describe('rendering component', () => {
    it('renders successfully', () => {
      expect(output.getElement()).not.toBeNull();
    });

    it('render tooltip with arrow, when had prop "arrow"', () => {
      expect(output).toHaveProp({ tooltipText: 'TooltipText' });
      expect(output).toHaveProp({ arrow: true });
      expect(output.props().isClickable).toBeFalsy();
      expect(output.simulate('focus').find('.arrow')).toBeTruthy();
    });
  });

  describe('handleArrowRef method', () => {
    it('set null value to arrowRef field in state', () => {
      output.instance().handleArrowRef(null);
      expect(output.state().arrowRef).toBeNull();
    });
  });

  describe('handleTooltipClose method', () => {
    it('set false value to open field in state', () => {
      output.instance().handleTooltipClose();
      expect(output.state().open).toBeFalsy();
    });
  });

  describe('handleTooltipOpen method', () => {
    it('set true value to open field in state', () => {
      output.instance().handleTooltipOpen();
      expect(output.state().open).toBeTruthy();
    });
  });
});
