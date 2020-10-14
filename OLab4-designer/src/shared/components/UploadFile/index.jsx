import React from 'react';

import UploadIcon from '../../assets/icons/add.svg';

import { UploadButton } from './styles';

const UploadFile = () => (
  <label htmlFor="upload_file">
    <input
      type="file"
      accept="image/*"
      style={{ display: 'none' }}
      id="upload_file"
      multiple
    />
    <UploadButton>
      <UploadIcon />
      Add files
    </UploadButton>
  </label>
);

export default UploadFile;
