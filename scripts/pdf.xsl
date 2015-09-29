<?xml version="1.0" encoding="ASCII"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:exsl="http://exslt.org/common" xmlns:cf="http://docbook.sourceforge.net/xmlns/chunkfast/1.0" xmlns:ng="http://docbook.org/docbook-ng" xmlns:db="http://docbook.org/ns/docbook" xmlns="http://www.w3.org/1999/xhtml" version="1.0" exclude-result-prefixes="exsl cf ng db">

<!-- This file contains overrides for output for e-books. -->
<!-- Note that PDF is normally made with the fo stylesheets, and epub with
     the epub stylesheets, from the docbook-xsl project. -->

<!-- Use outline numbering for sections. -->
<xsl:param name="section.autolabel" select="1"/>
<xsl:param name="section.autolabel.max.depth">1</xsl:param>
<xsl:param name="section.label.includes.component.label" select="1"/>
<xsl:param name="preface.autolabel">i</xsl:param>

<!-- Set the maximum depth for tables of contents to 2. -->
<xsl:variable name="toc.max.depth">
  <xsl:value-of select="'2'" />
</xsl:variable>

<!-- Waste less space by not indenting all text. -->
<xsl:param name="body.start.indent">0pt</xsl:param>

<!-- Do not show the URL after links -->
<xsl:param name="ulink.show" select="0"></xsl:param>

<!-- Better link properties. -->
<xsl:attribute-set name="xref.properties">
  <xsl:attribute name="font-style">italic</xsl:attribute>
  <xsl:attribute name="text-decoration">underline</xsl:attribute>
  <xsl:attribute name="color">blue</xsl:attribute>
</xsl:attribute-set>

<!-- Images -->
<xsl:param name="default.image.width">12cm</xsl:param>

</xsl:stylesheet>