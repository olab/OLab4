import styled from 'styled-components';

export const MapTitleWrapper = styled.form`
  display: flex;
  align-items: center;
`;

const styles = () => ({
  input: {
    padding: '4px 20px 4px 0px',
    boxSizing: 'border-box',
    maxWidth: '10rem',
  },
  formControl: {
    marginRight: '10px',
  },
  formHelperText: {
    marginTop: '4px',
    fontSize: '10px',
  },
  pencilIcon: {
    height: 19,
    width: 19,
    marginBottom: 5,
  },
});

export default styles;
