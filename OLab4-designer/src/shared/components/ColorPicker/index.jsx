// @flow
import React, { PureComponent } from 'react';
import { GithubPicker } from 'react-color';
import { InputLabel, ClickAwayListener } from '@material-ui/core';

import type {
  IColorType,
  IColorPickerProps,
  IColorPickerState,
} from './types';

import {
  LinkColorItem,
  ColorPickerWrapper,
  GithubPickerWrapper,
} from './styles';

class ColorPicker extends PureComponent<IColorPickerProps, IColorPickerState> {
  state: IColorPickerState = {
    isOpen: false,
  };

  handleChange = (color: IColorType): void => {
    const { hex } = color;
    const { onChange } = this.props;

    onChange(hex);

    this.close();
  }

  toggle = (): void => {
    this.setState(({ isOpen }) => ({
      isOpen: !isOpen,
    }));
  }

  close = (): void => {
    this.setState({ isOpen: false });
  }

  render() {
    const { isOpen } = this.state;
    const { label, color } = this.props;

    return (
      <ColorPickerWrapper>
        <InputLabel>{label}</InputLabel>
        <div>
          <LinkColorItem
            color={color}
            onClick={this.toggle}
          />
          {isOpen && (
            <ClickAwayListener onClickAway={this.close}>
              <GithubPickerWrapper>
                <GithubPicker
                  color={color}
                  onChange={this.handleChange}
                />
              </GithubPickerWrapper>
            </ClickAwayListener>
          )}
        </div>
      </ColorPickerWrapper>
    );
  }
}

export default ColorPicker;
