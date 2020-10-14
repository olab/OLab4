// @flow
import React, { Component, Fragment } from 'react';
import { withStyles } from '@material-ui/core/styles';
import { Tooltip, ClickAwayListener } from '@material-ui/core';

import type { State, Props } from './types';
import styles from './styles';

export class CustomizedTooltip extends Component<Props, State> {
  state = {
    arrowRef: null,
    open: false,
  };

  handleArrowRef = (node: HTMLSpanElement | null): void => {
    this.setState({ arrowRef: node });
  };

  handleTooltipClose = (): void => {
    this.setState({ open: false });
  };

  handleTooltipOpen = (): void => {
    this.setState({ open: true });
  };

  render() {
    const {
      classes, children, tooltipText, arrow, isClickable,
    } = this.props;
    const { open, arrowRef } = this.state;
    const title = (
      <Fragment>
        {tooltipText}
        {arrow && <span className={classes.arrow} ref={this.handleArrowRef} />}
      </Fragment>
    );

    const customPropsWhenClickable = {
      onClose: this.handleTooltipClose,
      open,
      disableFocusListener: isClickable,
      disableHoverListener: isClickable,
      disableTouchListener: isClickable,
    };

    const popperPropsForArrow = {
      disablePortal: isClickable,
      popperOptions: {
        modifiers: {
          arrow: {
            enabled: Boolean(arrowRef),
            element: arrowRef,
          },
        },
      },
    };

    const customPopperProps = arrow ? popperPropsForArrow : {};
    const customProps = isClickable ? customPropsWhenClickable : {};

    const customClasses = {
      popper: arrow && classes.arrowPopper,
      tooltip: classes.styleTooltip,
    };

    const childComponent = isClickable
      ? React.cloneElement(children, { onClick: this.handleTooltipOpen })
      : children;

    const tooltip = (
      <Tooltip
        title={title}
        classes={customClasses}
        {...customProps}
        PopperProps={customPopperProps}
      >
        {childComponent}
      </Tooltip>
    );

    return isClickable
      ? <ClickAwayListener onClickAway={this.handleTooltipClose}>{tooltip}</ClickAwayListener>
      : tooltip;
  }
}

export default withStyles(styles)(CustomizedTooltip);
