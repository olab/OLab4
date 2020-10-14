// @flow
import React, { Fragment } from 'react';
import { withStyles } from '@material-ui/core/styles';
import { Slider } from '@material-ui/lab';
import {
  Button, ClickAwayListener, Grow, Paper, Popper, MenuList,
} from '@material-ui/core';

import DropDownIcon from '../../../../shared/assets/icons/dropdown.svg';
import ScaleIcon from '../../../../shared/assets/icons/scale.svg';

import type { IGraphControlProps, IGraphControlState } from './types';

import { SLIDER_STEPS } from './config';

import styles, {
  ZoomContainer, ZoomWrapper, ScaleIconWrapper,
} from './styles';

export class ZoomControls extends React.Component<IGraphControlProps, IGraphControlState> {
  constructor(props: IGraphControlProps) {
    super(props);

    this.state = {
      open: false,
    };

    this.anchorEl = React.createRef();
    this.arrowRef = React.createRef();
  }

  static defaultProps = {
    maxZoom: 1.5,
    minZoom: 0.15,
  };

  handleToggle = () => {
    this.setState(prevState => ({ open: !prevState.open }));
  }

  handleClose = () => {
    this.setState({ open: false });
  }

  // Modify current zoom of graph-view
  zoom = (e: any, sliderVal: number): number => {
    const {
      minZoom = 0, maxZoom = 0, zoomLevel, modifyZoom,
    } = this.props;

    const zoomLevelNext = this.sliderToZoom(sliderVal);
    const delta = zoomLevelNext - zoomLevel;

    if (zoomLevelNext <= maxZoom && zoomLevelNext >= minZoom) {
      return modifyZoom(delta);
    }

    if (zoomLevelNext <= maxZoom) {
      return 0;
    }

    return SLIDER_STEPS;
  };

  // Convert slider val (0-SLIDER_STEPS) to original zoom value range
  sliderToZoom(val: number) {
    const { minZoom = 0, maxZoom = 1 } = this.props;
    return val * (maxZoom - minZoom) / SLIDER_STEPS + minZoom;
  }

  // Convert zoom val (minZoom-maxZoom) to slider range
  zoomToSlider(val: number) {
    const { minZoom = 0, maxZoom = 1 } = this.props;
    return (val - minZoom) * SLIDER_STEPS / (maxZoom - minZoom);
  }

  anchorEl: { current: null | HTMLDivElement };

  arrowRef: { current: null | HTMLSpanElement };

  render() {
    const { open } = this.state;
    const {
      zoomToFit, maxZoom = 0, minZoom = 0, zoomLevel, classes,
    } = this.props;

    return (
      <Fragment>
        <Button
          ref={this.anchorEl}
          aria-owns={open ? 'menu-list-grow' : undefined}
          aria-haspopup="true"
        >
          <ScaleIconWrapper>
            <ScaleIcon onClick={zoomToFit} role="none" />
          </ScaleIconWrapper>
          <ZoomWrapper onClick={this.handleToggle} role="none">
            <ZoomContainer>
              {this.zoomToSlider(zoomLevel).toFixed(0)}
              %
            </ZoomContainer>
            <DropDownIcon role="none" />
          </ZoomWrapper>
        </Button>
        <Popper
          open={open}
          transition
          disablePortal
          placement="bottom"
          modifiers={{
            flip: {
              enabled: true,
            },
            arrow: {
              enabled: true,
              element: this.arrowRef,
            },
          }}
        >
          {({ TransitionProps }) => (
            <Fragment>
              <span ref={this.arrowRef} className={classes.arrow} />
              <Grow
                {...TransitionProps}
                id="menu-list-grow"
              >
                <Paper>
                  <ClickAwayListener onClickAway={this.handleClose}>
                    <MenuList>
                      <Slider
                        classes={{ root: classes.sliderWidth }}
                        value={this.zoomToSlider(zoomLevel)}
                        onChange={this.zoom}
                        onDragEnd={this.handleClose}
                        max={this.zoomToSlider(maxZoom)}
                        min={this.zoomToSlider(minZoom)}
                        step={1}
                      />
                    </MenuList>
                  </ClickAwayListener>
                </Paper>
              </Grow>
            </Fragment>
          )}
        </Popper>
      </Fragment>
    );
  }
}

export default withStyles(styles)(ZoomControls);
