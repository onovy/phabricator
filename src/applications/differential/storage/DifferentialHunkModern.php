<?php

final class DifferentialHunkModern extends DifferentialHunk {

  const DATATYPE_TEXT       = 'text';
  const DATATYPE_FILE       = 'file';

  const DATAFORMAT_RAW      = 'byte';
  const DATAFORMAT_DEFLATED = 'gzde';

  protected $dataType;
  protected $dataEncoding;
  protected $dataFormat;
  protected $data;

  private $rawData;

  public function getTableName() {
    return 'differential_hunk_modern';
  }

  public function getConfiguration() {
    return array(
      self::CONFIG_BINARY => array(
        'data' => true,
      ),
    ) + parent::getConfiguration();
  }

  public function setChanges($text) {
    $this->rawData = $text;

    $this->dataEncoding = $this->detectEncodingForStorage($text);
    $this->dataType = self::DATATYPE_TEXT;
    $this->dataFormat = self::DATAFORMAT_RAW;
    $this->data = $text;

    return $this;
  }

  public function getChanges() {
    return $this->getUTF8StringFromStorage(
      $this->getRawData(),
      $this->getDataEncoding());
  }

  public function save() {

    $type = $this->getDataType();
    $format = $this->getDataFormat();

    // Before saving the data, attempt to compress it.
    if ($type == self::DATATYPE_TEXT) {
      if ($format == self::DATAFORMAT_RAW) {
        $data = $this->getData();
        $deflated = PhabricatorCaches::maybeDeflateData($data);
        if ($deflated !== null) {
          $this->data = $deflated;
          $this->dataFormat = self::DATAFORMAT_DEFLATED;
        }
      }
    }

    return parent::save();
  }

  private function getRawData() {
    if ($this->rawData === null) {
      $type = $this->getDataType();
      $data = $this->getData();

      switch ($type) {
        case self::DATATYPE_TEXT:
          // In this storage type, the changes are stored on the object.
          $data = $data;
          break;
        case self::DATATYPE_FILE:
        default:
          throw new Exception(
            pht('Hunk has unsupported data type "%s"!', $type));
      }

      $format = $this->getDataFormat();
      switch ($format) {
        case self::DATAFORMAT_RAW:
          // In this format, the changes are stored as-is.
          $data = $data;
          break;
        case self::DATAFORMAT_DEFLATED:
          $data = PhabricatorCaches::inflateData($data);
          break;
        default:
          throw new Exception(
            pht('Hunk has unsupported data encoding "%s"!', $type));
      }

      $this->rawData = $data;
    }

    return $this->rawData;
  }

}
