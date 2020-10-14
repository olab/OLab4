import * as React from 'react';

import { mount } from 'enzyme';

import { ZoomControls } from '.';

const className = 'graph-controls';
const classes = {};

describe('ZoomControls component', () => {
  let output = null;
  let zoomToFit;
  let modifyZoom;
  beforeEach(() => {
    zoomToFit = jest.fn();
    modifyZoom = jest.fn();
    output = mount(<ZoomControls
      zoomLevel={0}
      minZoom={0.15}
      maxZoom={1.5}
      zoomToFit={zoomToFit}
      modifyZoom={modifyZoom}
      className={className}
      classes={classes}
    />);
    output.setState({ open: true });
  });

  describe('render method', () => {
    it('renders', () => {
      const slider = output.find('Slider');
      expect(slider.length).toEqual(1);
      expect(slider.props().min).toEqual(0);
      expect(slider.props().max).toEqual(100);
      expect(slider.props().value).toEqual(-11.11111111111111);
      expect(slider.props().step).toEqual(1);
    });

    it('renders with a custom min and max zoom', () => {
      output.setProps({
        maxZoom: 0.9,
        minZoom: 0,
      });
      const slider = output.find('Slider');
      expect(slider.props().min).toEqual(0);
      expect(slider.props().max).toEqual(100);
      expect(slider.props().value).toEqual(0);
    });
  });

  describe('zoom method', () => {
    it('calls modifyZoom callback with the new zoom delta', () => {
      output.instance().zoom({}, 55);
      expect(modifyZoom).toHaveBeenCalledWith(0.8925000000000001);
    });

    it('does not call modifyZoom callback when the zoom level is greater than max', () => {
      output.instance().zoom({}, 101);
      expect(modifyZoom).not.toHaveBeenCalled();
    });

    it('does not call modifyZoom callback when the zoom level is less than min', () => {
      output.instance().zoom({}, -1);
      expect(modifyZoom).not.toHaveBeenCalled();
    });
  });

  describe('zoomToSlider method', () => {
    it('converts a value to a decimal-based slider position', () => {
      output.setProps({
        maxZoom: 1.5,
        minZoom: 0.15,
      });
      const result = output.instance().zoomToSlider(10);
      expect(result).toEqual(729.6296296296296);
    });
  });
});
