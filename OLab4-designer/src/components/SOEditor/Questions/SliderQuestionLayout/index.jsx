// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { TextField, Divider } from '@material-ui/core';
import { EDITORS_FIELDS, QUESTION_TYPES } from '../../config';
import { LAYOUT_TYPES } from '../config';
import OutlinedSelect from '../../../../shared/components/OutlinedSelect';
import OutlinedInput from '../../../../shared/components/OutlinedInput';
import styles, { FieldLabel, SwitchWrapper } from '../../styles';
import Switch from '../../../../shared/components/Switch';
import type { ISliderQuestionLayoutProps } from './types';

function SliderQuestionLayout({
  onInputChange,
  onQuestionTypeChange,
  onSettingsChange,
  onSwitchChange,
  props,
  state,
}: ISliderQuestionLayoutProps) {
  const { classes } = props;
  const {
    description,
    feedback,
    isFieldsDisabled,
    name,
    showAnswer,
    showSubmit,
    settings,
    stem,
    questionType,
  } = state;

  const questionTypes = [QUESTION_TYPES[5]];
  const orientationTypes = [
    'ver',
    'hor',
  ];

  const layoutToOrientation = (layoutType) => {
    const index = LAYOUT_TYPES.findIndex(type => type === layoutType);
    if (index >= 0) {
      return orientationTypes[index];
    }
    return orientationTypes[0];
  };

  const orientationToLayout = (orientation) => {
    const index = orientationTypes.findIndex(type => type === orientation);
    if (index >= 0) {
      return LAYOUT_TYPES[index];
    }
    return LAYOUT_TYPES[0];
  };

  const settingsObject = JSON.parse(settings);

  const onLayoutTypeChange = (e: Event): void => {
    const { value } = (e.target: window.HTMLInputElement);
    e.target.name = 'orientation';
    e.target.value = layoutToOrientation(value);
    onSettingsChange(e);
  };

  const layoutType = orientationToLayout(settingsObject.orientation);

  return (
    <>
      <FieldLabel>
        {EDITORS_FIELDS.QUESTION_TYPES}
      </FieldLabel>
      <OutlinedSelect
        name="questionType"
        value={QUESTION_TYPES[questionType]}
        values={questionTypes}
        onChange={onQuestionTypeChange}
        disabled={isFieldsDisabled}
      />

      <FieldLabel>
        {EDITORS_FIELDS.MINVALUE}
        <OutlinedInput
          name="minValue"
          placeholder={EDITORS_FIELDS.MINVALUE}
          value={settingsObject.minValue}
          onChange={onSettingsChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.MAXVALUE}
        <OutlinedInput
          name="maxValue"
          placeholder={EDITORS_FIELDS.MAXVALUE}
          value={settingsObject.maxValue}
          onChange={onSettingsChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.DEFAULTVALUE}
        <OutlinedInput
          name="defaultValue"
          placeholder={EDITORS_FIELDS.DEFAULTVALUE}
          value={settingsObject.defaultValue}
          onChange={onSettingsChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.STEPVALUE}
        <OutlinedInput
          name="stepValue"
          placeholder={EDITORS_FIELDS.STEPVALUE}
          value={settingsObject.stepValue}
          onChange={onSettingsChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.NAME}
        <TextField
          multiline
          rows="1"
          name="name"
          placeholder={EDITORS_FIELDS.NAME}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={name}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.DESCRIPTION}
        <TextField
          multiline
          rows="1"
          name="description"
          placeholder={EDITORS_FIELDS.DESCRIPTION}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={description}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.STEM}
        <TextField
          multiline
          rows="1"
          name="stem"
          placeholder={EDITORS_FIELDS.STEM}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={stem}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.FEEDBACK}
        <TextField
          multiline
          rows="3"
          name="feedback"
          placeholder={EDITORS_FIELDS.FEEDBACK}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={feedback}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <FieldLabel>
        {EDITORS_FIELDS.LAYOUT_TYPE}
      </FieldLabel>
      <OutlinedSelect
        name="layoutType"
        value={layoutType}
        values={LAYOUT_TYPES}
        onChange={onLayoutTypeChange}
        disabled={isFieldsDisabled}
      />

      <SwitchWrapper>
        <Switch
          name="showAnswer"
          label={EDITORS_FIELDS.SHOW_ANSWER}
          labelPlacement="start"
          checked={showAnswer}
          onChange={onSwitchChange}
          disabled={isFieldsDisabled}
        />
        <Switch
          name="showSubmit"
          label={EDITORS_FIELDS.SHOW_SUBMIT}
          labelPlacement="start"
          checked={showSubmit}
          onChange={onSwitchChange}
          disabled={isFieldsDisabled}
        />
      </SwitchWrapper>

      <Divider />
    </>
  );
}

export default withStyles(styles)(SliderQuestionLayout);
