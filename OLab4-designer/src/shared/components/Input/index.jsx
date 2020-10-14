// @flow
import React, { PureComponent } from 'react';
import {
  Input as MaterialInput,
  InputLabel,
  FormControl,
  FormHelperText,
} from '@material-ui/core';

import type { IInputProps, IInputState } from './types';

class Input extends PureComponent<IInputProps, IInputState> {
  inputRef: { current: null | HTMLInputElement };

  static defaultProps = {
    autoComplete: false,
    autoFocus: false,
    disabled: false,
    errorText: '',
    fullWidth: false,
    name: '',
    placeholder: '',
    variant: 'standard',
  };

  constructor(props: IInputProps) {
    super(props);
    this.state = {
      value: '',
      standardErrorMessage: 'Please fill out this field',
      isShowError: false,
    };

    this.inputRef = React.createRef();
  }

  componentDidMount() {
    const { autoFocus } = this.props;

    if (autoFocus && this.inputRef.current) {
      this.inputRef.current.focus();
    }
  }

  handleChange = (e: Event): void => {
    const { value } = (e.target: window.HTMLInputElement);
    this.setState({
      value,
      isShowError: !value,
    });
  }

  handleBlur = (): void => {
    const { value } = this.state;

    if (!value) {
      this.setState({
        isShowError: true,
      });
    }
  }

  render() {
    const {
      value, isShowError, standardErrorMessage,
    } = this.state;
    const {
      label, autoComplete, disabled, errorText, fullWidth, name, placeholder, variant,
    } = this.props;

    return (
      <FormControl error={!!errorText || isShowError}>
        <InputLabel>{label}</InputLabel>
        <MaterialInput
          name={name}
          value={value}
          disabled={disabled}
          placeholder={placeholder}
          inputRef={this.inputRef}
          onChange={this.handleChange}
          onBlur={this.handleBlur}
          fullWidth={fullWidth}
          variant={variant}
          autoComplete={autoComplete ? 'on' : 'off'}
          error={!!errorText || isShowError}
        />

        {!!errorText && <FormHelperText>{errorText}</FormHelperText>}
        {isShowError && <FormHelperText>{standardErrorMessage}</FormHelperText>}
      </FormControl>
    );
  }
}

export default Input;
