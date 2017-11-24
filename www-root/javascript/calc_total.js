	function CalculateTotal(frm)
	{
		var percentage_total = 0;
		var temp_total = 0;
		
		// Run through all the form fields
		for(var i=0; i < frm.elements.length; ++i)
		{
			// Get the current field
			form_field = frm.elements[i];
			
			// Get the field's name
			form_name = form_field.name;
			
			// Is it a "percentage" field?
			if(form_name.substring(0,10) == "percentage")
			{
				// Get the quantity
				//quantity = parseInt(form_field.value);
				quantity = parseFloat(form_field.value);
				
				
				otherQuantity = FormatNumberField(form_field,2);
				//round_decimals(quantity, 2)
				
				// Update the total
				if(quantity == null || (quantity > 0 && checkNumeric(form_field,0,100,'','.','')))
				{
					temp_total += parseFloat(otherQuantity);
					
					if(temp_total > 100.00)
					{
						eval(frm.elements[i].value = "");
						alert("You cannot enter this value as the total will exceed 100%.");
						frm.elements[i].select();
						frm.elements[i].focus();
						return(false);
					}
					else
					{	
						percentage_total += parseFloat(otherQuantity);
						eval(frm.elements[i].value = otherQuantity);
					}
				}
				else if(frm.elements[i].value == 0)
				{
					eval(frm.elements[i].value = "0.00");
				}
				/*else
				{
					if(frm.elements[i].value == 0.00)
					{
						eval(frm.elements[i].value = "");
					}
				}*/
			}
		}

		// Display the total rounded to two decimal places
		//frm.TOTAL.value = round_decimals(percentage_total, 2)
		frm.total.value = percentage_total;
		eval(frm.total.value = FormatNumberField(frm.total,2));
		if(frm.total.value == 0 || frm.total.value == '')
		{
			eval(frm.total.value = "0.00");
		}
	}

	function FormatNumberField(Object,Decimals,Pad,Separator,PadChar)
	{
		// **********************************************************
		// Placed in the public domain by Affordable Production Tools
		// March 23, 1998
		// Web site: http://www.aptools.com/
		//
		// November 24, 1998 -- Error which allowed a null field
		// to remain null fixed. Now forces value to 0.
		//
		// December 2, 1998 -- Modified to allow specification of
		// pad character.
		//
		// This function formats a number in an HTML form field,
		// setting the decimal precision and right justifying the
		// number in the field. An optional decimal separator other
		// than '.' may be specified and an optional pad character
		// may be specified (default is space).
		//
		// Note that this function uses two other library functions,
		// FormatNumber() and PadLeft().
		//
		// Usage: Call the function with an onblur or onchange event
		// attached to the field:
		//
		// onblur="FormatNumberField(this,Decimals,Pad,[Separator],[PadChar])"
		// where Decimals is the number of decimals desired and Pad
		// is the size of the field.
		// **********************************************************
		if(Object.value == "")
		{
			Object.value = "0.00";
		}
		
		if(Object == null)
		{
			return(null);
		}
		
		Separator += ""      // Force argument to string.
		
		if((Separator == "") || (Separator.length > 1))
		{
			Separator = ".";
		}
		
		PadChar += "";
		
		if((PadChar == "") || (!(PadChar.length == 1)))
		{
			PadChar = " ";
		}
	
		Object.value = FormatNumber(Object.value,Decimals,Separator);
		Object.value = PadLeft(Object.value,Pad,PadChar);
	
		return(Object.value);
	}

	function FormatNumber(Number,Decimals,Separator)
	{
		// **********************************************************
		// Placed in the public domain by Affordable Production Tools
		// March 21, 1998
		// Web site: http://www.aptools.com/
		//
		// November 24, 1998 -- Error which allowed a null value
		// to remain null fixed. Now forces value to 0.
		//
		// October 28, 2001 -- Modified to provide leading 0 for fractional number
		// less than 1.
		//
		// This function accepts a number to format and number
		// specifying the number of decimal places to format to. May
		// optionally use a separator other than '.' if specified.
		//
		// If no decimals are specified, the function defaults to
		// two decimal places. If no number is passed, the function
		// defaults to 0. Decimal separator defaults to '.' .
		//
		// If the number passed is too large to format as a decimal
		// number (e.g.: 1.23e+25), or if the conversion process
		// results in such a number, the original number is returned
		// unchanged.
		// **********************************************************
		Number += "";          // Force argument to string.
		Decimals += "";        // Force argument to string.
		Separator += "";       // Force argument to string.
		
		if((Separator == "") || (Separator.length > 1))
		{
			Separator = ".";
		}
		if(Number.length == 0)
		{
			Number = "0";
		}
		
		var OriginalNumber = Number;  // Save for number too large.
		var Sign = 1;
		var Pad = "";
		var Count = 0;
		
		if(OriginalNumber == 0.00)
		{
			return(OriginalNumber);
		}
		else if(OriginalNumber != 0.00)
		{
			// If no number passed, force number to 0.
			if(parseFloat(Number))
			{
				Number = parseFloat(Number);
			} 
			else 
			{
				Number = 0;
			}
			
			// If no decimals passed, default decimals to 2.
			if((parseInt(Decimals,10)) || (parseInt(Decimals,10) == 0))
			{
				Decimals = parseInt(Decimals,10);
			} 
			else 
			{
				Decimals = 2;
			}
			
			if(Number < 0)
			{
				Sign = -1;         // Remember sign of Number.
				Number *= Sign;    // Force absolute value of Number.
			}
			if(Decimals < 0)
			{
				Decimals *= -1;    // Force absolute value of Decimals.
			}
			
			// Next, convert number to rounded integer and force to string value.
			// (Number contains 1 extra digit used to force rounding)
			Number = "" + Math.floor(Number * Math.pow(10,Decimals + 1) + 5);
			
			if((Number.substring(1,2) == '.')||((Number + '')=='NaN'))
			{
				return(OriginalNumber); // Number too large to format as specified.
			}
			
			// If length of Number is less than number of decimals requested +1,
			// pad with zeros to requested length.
			if(Number.length < Decimals +1) // Construct pad string.
			{
				for(Count = Number.length; Count <= Decimals; Count++)
				{
					Pad += "0";
				}
			}
			
			Number = Pad + Number; // Pad number as needed.
			
			if(Decimals == 0)
			{
				// Drop extra digit -- Decimal portion is formatted.
				Number = Number.substring(0, Number.length -1);
			} 
			else 
			{
				// Or, format number with decimal point and drop extra decimal digit.
				Number = Number.substring(0,Number.length - Decimals -1) + Separator + Number.substring(Number.length - Decimals -1, Number.length -1);
			}
			
			if((Number == "") || (parseFloat(Number) < 1))
			{
				Number="0"+Number; // Force leading 0 for |Number| less than 1.
			}
			
			if(Sign == -1)
			{
				Number = "-" + Number;  // Set sign of number.
			}
			
			return(Number);
		}
	}

	function PadLeft(String,Length,PadChar)
	{
		// **********************************************************
		// Placed in the public domain by Affordable Production Tools
		// April 1, 1998
		// Web site: http://www.aptools.com/
		//
		// December 2, 1998 -- Modified to allow specification of
		// pad character.
		//
		// This function accepts a number or string, and a number
		// specifying the desired length. If the length is greater
		// than the length of the value passed, the value is padded
		// with spaces (default) or the specified pad character
		// to the length specified.
		//
		// The function is useful in right justifying numbers or
		// strings in HTML form fields.
		// **********************************************************
		String += "";       // Force argument to string.
		Length += "";       // Force argument to string.
		PadChar += "";     // Force argument to string.
		
		if((PadChar == "") || (!(PadChar.length == 1)))
		{
			PadChar = " ";
		}
		
		var Count = 0;
		var PadLength = 0;
		Length = parseInt(0 + Length,10);
		
		if(Length <= String.length) // No padding necessary.
		{
			return(String);
		}
		
		PadLength = Length - String.length;
		
		for(Count = 0; Count < PadLength; Count++)
		{
			String = PadChar + String;
		}
		return(String);
	}

	//    function round_decimals(original_number, decimals)
	//    {
	//        var result1 = original_number * Math.pow(10, decimals)
	//        var result2 = Math.round(result1 * 0.6 * 1.0 * 1.5)
	//        var result3 = result2 / Math.pow(10, decimals)
	//        return pad_with_zeros(result3, decimals)
	//    }

	function pad_with_zeros(rounded_value, decimal_places) 
	{
		// Convert the number to a string
		var value_string = rounded_value.toString();
		
		// Locate the decimal point
		var decimal_location = value_string.indexOf(".");
		
		// Is there a decimal point?
		if(decimal_location == -1) 
		{
			// If no, then all decimal places will be padded with 0s
			decimal_part_length = 0;
			
			// If decimal_places is greater than zero, tack on a decimal point
			value_string += decimal_places > 0 ? "." : "";
		}
		else 
		{
			// If yes, then only the extra decimal places will be padded with 0s
			decimal_part_length = value_string.length - decimal_location - 1;
		}
		
		// Calculate the number of decimal places that need to be padded with 0s
		var pad_total = decimal_places - decimal_part_length;
		
		if(pad_total > 0) 
		{
			// Pad the string with 0s
			for(var counter = 1; counter <= pad_total; counter++)
			{
				value_string += "0";
			}
		}
		return value_string;
	}
	
	// Reusable functions to check for numerics
	function checkNumeric(objName,minval, maxval,comma,period,hyphen)
	{
		var numberfield = objName;
		
		if (chkNumeric(objName,minval,maxval,comma,period,hyphen) == false)
		{
			numberfield.select();
			numberfield.focus();
			return false;
		}
		else
		{
			return true;
		}
	}
	
	function chkNumeric(objName,minval,maxval,comma,period,hyphen)
	{
		// only allow 0-9 be entered, plus any values passed
		// (can be in any order, and don't have to be comma, period, or hyphen)
		// if all numbers allow commas, periods, hyphens or whatever,
		// just hard code it here and take out the passed parameters
		var checkOK = "0123456789" + comma + period + hyphen;
		var checkStr = objName;
		var allValid = true;
		var decPoints = 0;
		var allNum = "";
		
		for (i = 0;  i < checkStr.value.length;  i++)
		{
			ch = checkStr.value.charAt(i);
			/*alert(ch);*/
			for (j = 0;  j < checkOK.length;  j++)
			{
				if (ch == checkOK.charAt(j))
				{
					break;
				}
			}
			
	          if (j == checkOK.length)
	          {
	              allValid = false;
	              break;
	          }
	          if (ch != ",")
	          {
				allNum += ch;
	          }
		}
		if (!allValid)
		{
			alertsay = "Please enter only these values \""
			alertsay = alertsay + checkOK + "\" in the \"" + checkStr.name + "\" field."
			alert(alertsay);
			return (false);
		}
	
		// set the minimum and maximum
		var chkVal = allNum;
		var prsVal = parseInt(allNum);
		if (chkVal != "" && !(prsVal >= minval && prsVal <= maxval))
		{
			alertsay = "Please enter a value greater than or "
			alertsay = alertsay + "equal to \"" + minval + "\" and less than or "
			alertsay = alertsay + "equal to \"" + maxval + "\" in the \"" + checkStr.name + "\" field."
			alert(alertsay);
			return (false);
		}
	}