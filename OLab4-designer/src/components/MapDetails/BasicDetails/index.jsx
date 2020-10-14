// @flow
import React from 'react';
import { withStyles } from '@material-ui/core/styles';

import TextEditor from '../../../shared/components/TextEditor';
import OutlinedInput from '../../../shared/components/OutlinedInput';

import type { BasicDetailsProps as IProps } from './types';

import styles, { ContainerKeywords } from './styles';
import { ContainerTab, ContentTitle } from '../styles';

const BasicDetails = ({
  details, handleInputChange, handleEditorChange,
}: IProps): React$Element<any> => (
  <ContainerTab>
    <OutlinedInput
      name="name"
      label="Title"
      value={details.name}
      onChange={handleInputChange}
      fullWidth
    />
    <ContentTitle>Description</ContentTitle>
    <TextEditor
      editorId="description"
      width={800}
      height={300}
      text={details.description}
      handleEditorChange={handleEditorChange}
    />
    <ContainerKeywords>
      <OutlinedInput
        name="keywords"
        label="Keywords"
        value={details.keywords}
        onChange={handleInputChange}
        fullWidth
      />
    </ContainerKeywords>
    <div>
      <b>Creator:</b>
      {` ${details.author}`}
    </div>
  </ContainerTab>
);

export default withStyles(styles)(BasicDetails);
