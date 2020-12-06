// @flow
import React, { PureComponent } from 'react';
import { Button, Tooltip } from '@material-ui/core';
import type { ITitledButtonProps, ITitledButtonState } from './types';

export class TitledButton extends PureComponent<ITitledButtonProps, ITitledButtonState> {
  static defaultProps = {
    isDisabled: false,
    variant: 'contained',
    color: 'default',
  };

  render() {
    const {
      className,
      color,
      isDisabled,
      label,
      onClick,
      title,
      type,
      variant,
    } = this.props;

    return (
      <Tooltip title={title}>
        <Button
          className={className}
          color={color}
          disabled={isDisabled}
          onClick={onClick}
          type={type}
          variant={variant}
        >
          {label}
        </Button>
      </Tooltip>
    );
  }
}

export default TitledButton;
