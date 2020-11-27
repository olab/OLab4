// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import { TextField, Divider } from '@material-ui/core';
import { QUESTION_TYPES, EDITORS_FIELDS, TEXTENTRY_QUESTION_TYPES } from '../../config';
import OutlinedSelect from '../../../../shared/components/OutlinedSelect';
import styles, { FieldLabel, SwitchWrapper } from '../../styles';
import Switch from '../../../../shared/components/Switch';
import type { ITextQuestionLayoutProps } from './types';
import { DEFAULT_WIDTH, DEFAULT_HEIGHT } from '../config';

function TextQuestionLayout({
  onInputChange,
  onQuestionTypeChange,
  onSelectChange,
  onSwitchChange,
  props,
  state,
}: ITextQuestionLayoutProps) {
  const { classes } = props;
  const {
    description,
    feedback,
    height,
    isFieldsDisabled,
    isPrivate,
    name,
    showSubmit,
    prompt,
    questionType,
    stem,
    width,
  } = state;

  const widthChoices = [];
  const heightChoices = [];
  for (let i = DEFAULT_WIDTH.MIN; i <= DEFAULT_WIDTH.MAX; i += DEFAULT_WIDTH.STEP) {
    widthChoices.push(i);
  }
  for (let i = DEFAULT_HEIGHT.MIN; i <= DEFAULT_HEIGHT.MAX; i += DEFAULT_HEIGHT.STEP) {
    heightChoices.push(i);
  }

  return (
    <>
      <FieldLabel>
        {EDITORS_FIELDS.QUESTION_TYPES}
      </FieldLabel>
      <OutlinedSelect
        name="questionType"
        value={TEXTENTRY_QUESTION_TYPES[questionType]}
        values={Object.values(TEXTENTRY_QUESTION_TYPES)}
        onChange={onQuestionTypeChange}
        disabled={isFieldsDisabled}
      />

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
        {EDITORS_FIELDS.WIDTH}
      </FieldLabel>
      <OutlinedSelect
        name="width"
        value={width}
        values={widthChoices}
        onChange={onSelectChange}
        disabled={isFieldsDisabled}
      />
      {(QUESTION_TYPES[questionType] === QUESTION_TYPES[2]) && (
        <>
          <FieldLabel>
            {EDITORS_FIELDS.HEIGHT}
          </FieldLabel>
          <OutlinedSelect
            name="height"
            value={height}
            values={heightChoices}
            onChange={onSelectChange}
            disabled={isFieldsDisabled}
          />
        </>
      )}
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
        {EDITORS_FIELDS.PROMPTTEXT}
        <TextField
          multiline
          rows="3"
          name="prompt"
          placeholder={EDITORS_FIELDS.PROMPTTEXT}
          className={classes.textField}
          margin="normal"
          variant="outlined"
          value={prompt}
          onChange={onInputChange}
          disabled={isFieldsDisabled}
          fullWidth
        />
      </FieldLabel>

      <SwitchWrapper>
        <Switch
          name="isPrivate"
          label={EDITORS_FIELDS.IS_PRIVATE}
          labelPlacement="start"
          checked={isPrivate}
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

export default withStyles(styles)(TextQuestionLayout);
