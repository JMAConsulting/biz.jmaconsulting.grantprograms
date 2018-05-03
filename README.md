biz.jmaconsulting.grantprograms
===============================

After installation, ensure that appropriate roles are given the following permissions using your CMS permissions page:
CiviGrant: access CiviGrant [required]
CiviGrant: edit grants [recommended for grant program staff]
CiviGrant: delete in CiviGrant [[recommended for grant program supervisors, though perhaps no role should get this]
CiviCRM Grant Program: edit grant finance [recommended for grant program supervisors]
CiviCRM Grant Program: edit grant programs in CiviGrant [recommended for grant program staff]
CiviCRM Grant Program: cancel payments in CiviGrant [recommended for grant program staff]
CiviCRM Grant Program: edit payments in CiviGrant [recommended for grant program staff]
CiviCRM Grant Program: create payments in CiviGrant [recommended for grant program staff]

We recommend installing wkhtmltopdf since it works better than the DomPDF library included in CiviCRM. Important Note: Generating PDF checks results in a string of PHP notices related to DomPDF. To fix this without installing wkhtmltopdf, manually download  a missing font library that is not shipped with DomPDF from https://github.com/dompdf/dompdf and extract its fonts folder into civicrm/packages/dompdf/lib/fonts.

